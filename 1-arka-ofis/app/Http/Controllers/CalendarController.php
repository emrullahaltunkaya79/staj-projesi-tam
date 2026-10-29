<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use App\Services\ReceivableService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Ödeme takvimi: ay ızgarasında fatura vadeleri ve tekrarlayan fatura
 * kesim günleri; altında yaklaşan/geciken ödemelerin eylem listesi.
 */
class CalendarController extends Controller
{
    public function index(Request $request, ReceivableService $receivable)
    {
        $companyId = $request->user()->company_id;

        // Görüntülenen ay: ?ay=10&yil=2026 (varsayılan: bu ay)
        $ay = (int) $request->query('ay', now()->month);
        $yil = (int) $request->query('yil', now()->year);

        if ($ay < 1 || $ay > 12) {
            $ay = now()->month;
        }
        if ($yil < 2000 || $yil > 2100) {
            $yil = now()->year;
        }

        $cursor = Carbon::create($yil, $ay, 1)->startOfMonth();

        // Vade olayları: kalan tutarı olan satış faturaları (FIFO kalanına göre)
        $rows = $receivable->companyAllocation($companyId)
            ->filter(fn ($row) => $row['remaining'] > 0.001 && $row['invoice']->due_date)
            ->values();

        // Ay ızgarası olayları
        $monthStart = $cursor->copy();
        $monthEnd = $cursor->copy()->endOfMonth();

        $dayEvents = [];
        foreach ($rows as $row) {
            $date = $row['invoice']->due_date;
            if ($date->between($monthStart, $monthEnd)) {
                $dayEvents[$date->toDateString()][] = [
                    'tip' => $row['days_overdue'] > 0 ? 'geciken' : 'vade',
                    'baslik' => $row['invoice']->contact->name,
                    'fatura_no' => $row['invoice']->invoice_no,
                    'tutar' => $row['remaining'],
                    'url' => route('faturalar.show', $row['invoice']->id),
                ];
            }
        }

        // Tekrarlayan fatura kesim günleri (aktif kurallar)
        foreach (RecurringInvoice::where('company_id', $companyId)->where('is_active', true)->get() as $rule) {
            $day = min($rule->day_of_month, $monthEnd->day);
            $date = $cursor->copy()->day($day);
            $dayEvents[$date->toDateString()][] = [
                'tip' => 'kesim',
                'baslik' => $rule->title.' — '.$rule->contact->name,
                'fatura_no' => 'TKR',
                'tutar' => collect($rule->items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']) * (1 + (float) $rule->tax_rate / 100),
                'url' => route('tekrarlayan.index'),
            ];
        }

        // Yaklaşan ödemeler: gecikenler + gelecek 30 gün (kalan tutarlı)
        $today = now()->toDateString();
        $in30 = now()->addDays(30)->toDateString();

        $odemeListesi = $rows
            ->filter(fn ($row) => $row['invoice']->due_date->toDateString() <= $in30)
            ->map(fn ($row) => [
                'tarih' => $row['invoice']->due_date,
                'cari' => $row['invoice']->contact->name,
                'fatura_no' => $row['invoice']->invoice_no,
                'tutar' => $row['remaining'],
                'gun' => $row['days_overdue'] > 0
                    ? '-'.$row['days_overdue'].' gün (gecikti)'
                    : $row['invoice']->due_date->startOfDay()->diffInDays(now()->startOfDay()).' gün',
                'geciken' => $row['days_overdue'] > 0,
                'bugun' => $row['invoice']->due_date->toDateString() === $today,
                'url' => route('faturalar.show', $row['invoice']->id),
            ])
            ->sortBy(fn ($item) => $item['tarih']->toDateString())
            ->values();

        return view('calendar.index', [
            'cursor' => $cursor,
            'dayEvents' => $dayEvents,
            'list' => $odemeListesi,
            'selectedDay' => (string) $request->query('gun'),
            'monthTotal' => collect($dayEvents)->flatten(1)->where('tip', '!=', 'kesim')->sum('tutar'),
            'ay' => $ay,
            'yil' => $yil,
        ]);
    }
}
