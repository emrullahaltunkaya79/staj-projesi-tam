<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

/**
 * Vadesi gelen tekrarlayan faturaları keser (aylık abonelik/kira vb.).
 * Elle de çalıştırılabilir: php artisan tekrarlayan:faturalar-kes
 */
class CutRecurringInvoices extends Command
{
    protected $signature = 'tekrarlayan:faturalar-kes';

    protected $description = 'Vadesi gelen tekrarlayan fatura kurallarını işler ve satış faturalarını keser';

    public function handle(InvoiceService $invoiceService): int
    {
        $cut = 0;
        $failed = 0;

        foreach (RecurringInvoice::with('contact')->get() as $rule) {
            if (! $rule->isDueToday()) {
                continue;
            }

            try {
                $invoice = $invoiceService->createSaleInvoice($rule->company_id, [
                    'contact_id' => $rule->contact_id,
                    'issue_date' => now()->toDateString(),
                    'tax_rate' => (float) $rule->tax_rate,
                    'notes' => $rule->title.' (otomatik)',
                    'items' => array_map(fn ($item) => [
                        'product_id' => $item['product_id'],
                        'quantity' => (float) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                    ], $rule->items ?? []),
                ]);

                $rule->update(['last_run_at' => now()->toDateString()]);
                $cut++;

                $this->info("{$rule->title}: {$invoice->invoice_no} kesildi ({$rule->contact->name}).");
            } catch (\Throwable $e) {
                // Örn. stok yetersiz: kural aynen kalır, ertesi gün tekrar denenir
                $failed++;
                $this->error("{$rule->title}: kesilemedi — {$e->getMessage()}");
            }
        }

        $this->info("Tamamlandı: {$cut} fatura kesildi, {$failed} hata.");

        return self::SUCCESS;
    }
}
