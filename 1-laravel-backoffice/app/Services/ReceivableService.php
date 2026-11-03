<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * Alacak takibi servisi: tahsilatları satış faturalarına FIFO ile dağıtır,
 * kalan tutar, gecikme günü ve yaşlandırma kovalarını (0-30 / 31-60 / 60+) hesaplar.
 */
class ReceivableService
{
    /**
     * Şirket genelinde FIFO dağıtımı.
     * Yalnızca tahsilat yönündeki ödemeler dağıtılır; tamamen iade edilmiş
     * faturalar tahsil edilecek tutar bırakmaz.
     *
     * @return Collection her satış faturası için:
     *   invoice, paid (tahsil edilen), remaining (kalan), days_overdue (gecikme günü, 0 = gecikmede değil)
     */
    public function companyAllocation(int $companyId): Collection
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->where('type', 'satis')
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        // İade edilmiş faturalar (kalan tahsilat sıfır kabul edilir)
        $refundedIds = Invoice::where('company_id', $companyId)
            ->whereNotNull('refunded_invoice_id')
            ->pluck('refunded_invoice_id')
            ->flip();

        $remainingPaid = (float) Payment::where('company_id', $companyId)
            ->where('direction', 'tahsilat')
            ->sum('amount');

        return $invoices->map(function (Invoice $invoice) use (&$remainingPaid, $refundedIds) {
            $applied = min((float) $invoice->total, $remainingPaid);
            $remainingPaid = round($remainingPaid - $applied, 2);

            if ($refundedIds->has($invoice->id)) {
                $applied = (float) $invoice->total; // iade ile tamamen kapatıldı sayılır
            }
            $remaining = round((float) $invoice->total - $applied, 2);

            $daysOverdue = 0;
            if ($remaining > 0.001 && $invoice->due_date && $invoice->due_date->endOfDay()->isPast()) {
                // Carbon 3 diffInDays işaretli döndürdüğü için mutlak değer alınır
                $daysOverdue = (int) ceil(abs(now()->diffInDays($invoice->due_date)));
            }

            return [
                'invoice' => $invoice,
                'paid' => round($applied, 2),
                'remaining' => $remaining,
                'days_overdue' => $daysOverdue,
            ];
        })->keyBy(fn ($row) => $row['invoice']->id);
    }

    /**
     * Vadesi geçmiş (kalan tutarı olan) faturalar.
     */
    public function overdueInvoices(int $companyId): Collection
    {
        return $this->companyAllocation($companyId)
            ->filter(fn ($row) => $row['days_overdue'] > 0)
            ->sortByDesc('days_overdue');
    }

    /**
     * Yaşlandırma kovaları: kalan alacakların gecikme durumuna göre dağılımı.
     */
    public function agingBuckets(int $companyId): array
    {
        $buckets = [
            ['label' => 'Vadesi gelmemiş', 'count' => 0, 'amount' => 0.0],
            ['label' => '1-30 gün gecikmiş', 'count' => 0, 'amount' => 0.0],
            ['label' => '31-60 gün gecikmiş', 'count' => 0, 'amount' => 0.0],
            ['label' => '60+ gün gecikmiş', 'count' => 0, 'amount' => 0.0],
        ];

        foreach ($this->companyAllocation($companyId) as $row) {
            if ($row['remaining'] <= 0.001) {
                continue;
            }

            $days = $row['days_overdue'];
            $index = 0;
            if ($days > 0) {
                $index = $days <= 30 ? 1 : ($days <= 60 ? 2 : 3);
            }

            $buckets[$index]['count']++;
            $buckets[$index]['amount'] = round($buckets[$index]['amount'] + $row['remaining'], 2);
        }

        return $buckets;
    }
}
