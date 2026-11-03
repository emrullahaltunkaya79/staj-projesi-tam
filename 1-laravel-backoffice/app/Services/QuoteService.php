<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Teklif servisi: teklif oluşturma, durum değiştirme ve
 * onaylı teklifi satış faturasına dönüştürme.
 *
 * Teklif hiçbir muhasebe etkisi yaratmaz (stok/bakiye değişmez);
 * faturalaştırma anında InvoiceService devreye girer (stok kontrolü dahil).
 */
class QuoteService
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    /**
     * @throws ValidationException
     */
    public function createQuote(int $companyId, array $data): Quote
    {
        $draft = $this->buildDraft($companyId, $data);

        $quote = Quote::create($draft['fields'] + [
            'company_id' => $companyId,
            'quote_no' => 'TMP-'.uniqid(),
            'status' => 'acik',
        ]);

        $quote->update(['quote_no' => 'TK-'.str_pad((string) $quote->id, 6, '0', STR_PAD_LEFT)]);

        foreach ($draft['lines'] as $line) {
            $quote->items()->create($line);
        }

        return $quote->fresh(['items', 'contact']);
    }

    /**
     * Ortak taslak hesabı: doğrulama + kalem/tutar hesabı (kalıcı kayıt yapmaz).
     *
     * @return array{fields: array<string, mixed>, lines: array<int, array<string, mixed>>}
     *
     * @throws ValidationException
     */
    private function buildDraft(int $companyId, array $data): array
    {
        $contact = Contact::where('company_id', $companyId)
            ->where('id', $data['contact_id'])
            ->first();

        if (! $contact || $contact->type !== 'musteri') {
            throw ValidationException::withMessages([
                'contact_id' => 'Teklif yalnızca müşteri carilere kesilebilir.',
            ]);
        }

        $items = collect($data['items'])->filter(fn ($item) => $item['product_id'] && $item['quantity'] > 0);
        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Teklife en az bir kalem eklemelisiniz.',
            ]);
        }

        // Ürün adlarını snapshot olarak sakla (ürün sonradan silinse bile teklif okunur kalsın);
        // sorgu şirket kapsamlı olduğundan başka şirketin ürünü kullanılamaz
        $products = \App\Models\Product::where('company_id', $companyId)
            ->whereIn('id', $items->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $lines = [];
        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);
            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => "Seçilen ürün (ID: {$item['product_id']}) bulunamadı.",
                ]);
            }

            $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            $subtotal = round($subtotal + $lineTotal, 2);
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => $lineTotal,
            ];
        }

        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total = round($subtotal + $taxAmount, 2);

        return [
            'fields' => [
                'contact_id' => $contact->id,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Yalnızca "Açık" durumundaki teklifler düzenlenebilir.
     *
     * @throws ValidationException
     */
    public function updateQuote(Quote $quote, int $companyId, array $data): Quote
    {
        if ($quote->company_id !== $companyId) {
            throw ValidationException::withMessages(['quote' => 'Teklif bulunamadı.']);
        }

        if ($quote->status !== 'acik') {
            throw ValidationException::withMessages(['quote' => 'Yalnızca "Açık" durumundaki teklifler düzenlenebilir.']);
        }

        $draft = $this->buildDraft($companyId, $data);

        $quote->items()->delete();
        foreach ($draft['lines'] as $line) {
            $quote->items()->create($line);
        }
        $quote->update($draft['fields']);

        return $quote->fresh(['items', 'contact']);
    }

    /**
     * Teklif durumu değiştirir (onaylandı / reddedildi / tekrar açık).
     *
     * @throws ValidationException
     */
    public function changeStatus(Quote $quote, string $status, int $companyId): Quote
    {
        if ($quote->company_id !== $companyId) {
            throw ValidationException::withMessages(['quote' => 'Teklif bulunamadı.']);
        }

        if ($quote->status === 'faturalandi') {
            throw ValidationException::withMessages(['quote' => 'Faturalanmış teklifin durumu değiştirilemez.']);
        }

        $quote->update(['status' => $status]);

        return $quote->fresh();
    }

    /**
     * Onaylı teklifi satış faturasına dönüştürür.
     * Stok kontrolü InvoiceService içinde yapılır; yetersizse dönüşüm rollback edilir.
     *
     * @throws ValidationException
     */
    public function convertToInvoice(Quote $quote, int $companyId): \App\Models\Invoice
    {
        return DB::transaction(function () use ($quote, $companyId) {
            // Teklifi kilitle ve doğrula
            $locked = Quote::where('company_id', $companyId)->where('id', $quote->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages(['quote' => 'Teklif bulunamadı.']);
            }

            if ($locked->status === 'faturalandi') {
                throw ValidationException::withMessages(['quote' => 'Bu teklif zaten faturalandırılmış.']);
            }

            if ($locked->status !== 'onaylandi') {
                throw ValidationException::withMessages(['quote' => 'Faturalaştırma için teklifin "Onaylandı" durumunda olması gerekiyor.']);
            }

            $locked->load('items');

            // InvoiceService kendi transaction'ını (savepoint) kullanır:
            // stok yetersizse tüm dönüşüm geri alınır ve teklif onaylı kalır
            $invoice = $this->invoiceService->createSaleInvoice($companyId, [
                'contact_id' => $locked->contact_id,
                'issue_date' => now()->toDateString(),
                'tax_rate' => (float) $locked->tax_rate,
                'notes' => "{$locked->quote_no} numaralı tekliften düzenlenmiştir.",
                'items' => $locked->items
                    ->map(fn ($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                    ])
                    ->all(),
            ]);

            $locked->update([
                'status' => 'faturalandi',
                'converted_invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });
    }
}
