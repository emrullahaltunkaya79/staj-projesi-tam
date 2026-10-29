<?php

namespace App\Console\Commands;

use App\Mail\DailySummaryMail;
use App\Models\Company;
use App\Models\Product;
use App\Services\ReceivableService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Günlük özet e-postası: her şirketin yöneticilerine
 * kritik stok ve vadesi geçen/yaklaşan faturaları bildirir.
 * Elle de çalıştırılabilir: php artisan bildirim:gundelik-ozet
 */
class SendDailySummary extends Command
{
    protected $signature = 'bildirim:gundelik-ozet';

    protected $description = 'Kritik stok ve vadesi geçen/yaklaşan faturalar için günlük özet e-postası gönderir';

    public function handle(ReceivableService $receivable): int
    {
        $sent = 0;

        foreach (Company::with('users')->get() as $company) {
            $admins = $company->users->where('role', 'admin');

            if ($admins->isEmpty()) {
                continue;
            }

            $allocation = $receivable->companyAllocation($company->id);

            // Vadesi geçen faturalar
            $overdue = $allocation->filter(fn ($row) => $row['days_overdue'] > 0)
                ->map(fn ($row) => [
                    'invoice_no' => $row['invoice']->invoice_no,
                    'contact' => $row['invoice']->contact->name,
                    'due_date' => $row['invoice']->due_date?->format('d.m.Y'),
                    'days' => $row['days_overdue'],
                    'remaining' => $row['remaining'],
                ])->values();

            // 7 gün içinde vadesi gelecek, kalan tutarı olan faturalar
            $upcoming = $allocation->filter(function ($row) {
                $invoice = $row['invoice'];

                return $row['remaining'] > 0.001
                    && $row['days_overdue'] === 0
                    && $invoice->due_date
                    && $invoice->due_date->isFuture()
                    && $invoice->due_date->diffInDays(now()) <= 7;
            })->map(fn ($row) => [
                'invoice_no' => $row['invoice']->invoice_no,
                'contact' => $row['invoice']->contact->name,
                'due_date' => $row['invoice']->due_date->format('d.m.Y'),
                'remaining' => $row['remaining'],
            ])->values();

            // Kritik stok (< 5)
            $lowStock = Product::where('company_id', $company->id)
                ->where('stock_quantity', '<', 5)
                ->orderBy('stock_quantity')
                ->get(['name', 'code', 'stock_quantity', 'unit'])
                ->map(fn ($p) => [
                    'name' => $p->name,
                    'code' => $p->code,
                    'stock' => $p->stock_quantity.' '.$p->unit,
                ]);

            // Bildirilecek bir şey yoksa e-posta gönderme
            if ($overdue->isEmpty() && $upcoming->isEmpty() && $lowStock->isEmpty()) {
                $this->line("{$company->name}: bildirilecek öğe yok, atlandı.");

                continue;
            }

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new DailySummaryMail(
                    $company->name,
                    $overdue->all(),
                    $upcoming->all(),
                    $lowStock->all()
                ));
                $sent++;
            }

            $this->info("{$company->name}: özet gönderildi (geciken: {$overdue->count()}, yaklaşan: {$upcoming->count()}, kritik stok: {$lowStock->count()}).");
        }

        $this->info("Toplam {$sent} e-posta gönderildi.");

        return self::SUCCESS;
    }
}
