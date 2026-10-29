<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fatura servisleri: satış ve alış faturaları.
 *
 * Her iki fatura türü de tek bir veritabanı işleminde (DB::beginTransaction/commit/rollBack)
 * ve satır kilidi (lockForUpdate) altında işlenir:
 *
 * SATIŞ (createSaleInvoice):
 *  1. Stok kontrolü — yetersizse "Yetersiz stok!" hatası ve tam rollback,
 *  2. Fatura + kalemler kaydedilir, stok DÜŞER, carinin bize borcu (bakiye) ARTAR.
 *
 * ALIŞ (createPurchaseInvoice):
 *  1. Cari tedarikçi olmalı,
 *  2. Fatura + kalemler kaydedilir, stok ARTAR, tedarikçiye borçlandığımız için
 *     cari bakiyesi EKSİLİR (negatif bakiye = cariye borçluyuz).
 *
 * Her stok değişimi StockMovement::record ile hareket geçmişine işlenir.
 */
class InvoiceService
{
    /**
     * @throws ValidationException
     */
    public function createSaleInvoice(int $companyId, array $data): Invoice
    {
        return $this->createInvoice($companyId, $data, 'satis');
    }

    /**
     * @throws ValidationException
     */
    public function createPurchaseInvoice(int $companyId, array $data): Invoice
    {
        return $this->createInvoice($companyId, $data, 'alis');
    }

    /**
     * Satış faturasını kısmen veya tamamen iade eder.
     *
     * $quantities: [product_id => iade miktarı] — boş/verilmeyen kalemler iade edilmez.
     * Kalan iade hakkı (orijinal miktar − önceki iadeler) aşılırsa işlem rollback edilir.
     * İade faturası ('IF-') yalnızca seçilen miktarlar için kesilir: stok geri yüklenir,
     * cari bakiyesi İADE TUTARI kadar düşer.
     *
     * @param  array<int, float>  $quantities
     *
     * @throws ValidationException
     */
    public function refundSaleInvoice(Invoice $invoice, int $companyId, array $quantities = []): Invoice
    {
        DB::beginTransaction();

        try {
            if ($invoice->company_id !== $companyId) {
                throw ValidationException::withMessages([
                    'invoice' => 'Fatura bulunamadı.',
                ]);
            }

            if ($invoice->type !== 'satis') {
                throw ValidationException::withMessages([
                    'invoice' => 'Yalnızca satış faturaları iade edilebilir.',
                ]);
            }

            $invoice->load(['items', 'refunds.items']);

            // Kalemler bazında kalan iade hakkı
            $remainingByProduct = [];
            foreach ($invoice->items as $item) {
                $remainingByProduct[(int) $item->product_id] = (float) $item->quantity;
            }
            foreach ($invoice->refunds as $previous) {
                foreach ($previous->items as $item) {
                    $pid = (int) $item->product_id;
                    if (isset($remainingByProduct[$pid])) {
                        $remainingByProduct[$pid] -= (float) $item->quantity;
                    }
                }
            }

            // İstek doğrulama: en az bir pozitif miktar ve kalan hakkı aşmayan değerler
            $lines = [];
            $subtotal = 0;
            foreach ($invoice->items as $item) {
                $pid = (int) $item->product_id;
                $requested = (float) ($quantities[$pid] ?? $quantities[(string) $pid] ?? 0);

                if ($requested < 0 || $requested > $remainingByProduct[$pid] + 0.001) {
                    throw ValidationException::withMessages([
                        'invoice' => sprintf(
                            '"%s" için iade miktarı en fazla %s olabilir.',
                            $item->product_name,
                            rtrim(rtrim(number_format($remainingByProduct[$pid], 2, ',', '.'), '0'), ',')
                        ),
                    ]);
                }

                if ($requested > 0) {
                    $lineTotal = round($requested * (float) $item->unit_price, 2);
                    $subtotal = round($subtotal + $lineTotal, 2);
                    $lines[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $requested,
                        'unit_price' => (float) $item->unit_price,
                        'line_total' => $lineTotal,
                    ];
                }
            }

            if (empty($lines)) {
                throw ValidationException::withMessages([
                    'invoice' => 'İade edilecek en az bir kalem miktarı girilmelidir (kalan iade hakkı: '.collect($remainingByProduct)->filter(fn ($v) => $v > 0)->sum().').',
                ]);
            }

            // Cariyi kilitle
            $contact = Contact::where('company_id', $companyId)
                ->where('id', $invoice->contact_id)
                ->lockForUpdate()
                ->first();

            if (! $contact) {
                throw ValidationException::withMessages([
                    'invoice' => 'Faturanın carisi bulunamadı.',
                ]);
            }

            $taxRate = (float) $invoice->tax_rate;
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $total = round($subtotal + $taxAmount, 2);

            $isFull = empty($quantities) ? false : collect($invoice->items)->every(fn ($item) => (float) ($quantities[(int) $item->product_id] ?? 0) >= $remainingByProduct[(int) $item->product_id] - 0.001);

            $refund = Invoice::create([
                'company_id' => $companyId,
                'type' => 'iade',
                'contact_id' => $contact->id,
                'invoice_no' => 'TMP-'.uniqid(),
                'issue_date' => now()->toDateString(),
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $isFull
                    ? "{$invoice->invoice_no} numaralı faturanın tam iadesidir."
                    : "{$invoice->invoice_no} numaralı faturanın kısmi iadesidir.",
                'refunded_invoice_id' => $invoice->id,
            ]);

            $refund->update(['invoice_no' => 'IF-'.str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT)]);

            // Ürünleri kilitle, stoğu geri yükle
            $products = Product::whereIn('id', collect($lines)->pluck('product_id')->filter())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lines as $line) {
                $refund->items()->create($line);

                $product = $products->get($line['product_id']);
                if ($product) {
                    $stockBefore = $product->stock_quantity;
                    Product::where('id', $product->id)->increment('stock_quantity', $line['quantity']);

                    StockMovement::record(
                        $product,
                        'iade',
                        'giris',
                        (float) $line['quantity'],
                        (float) ($stockBefore + $line['quantity']),
                        $refund,
                        "{$invoice->invoice_no} iadesi"
                    );
                }
            }

            // İade tutarı kadar müşterinin borcu düşer
            $contact->decrement('balance', $total);

            DB::commit();

            return $refund->fresh(['items.product', 'contact', 'refundOf']);
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private function createInvoice(int $companyId, array $data, string $type): Invoice
    {
        DB::beginTransaction();

        try {
            // Global scope oturum gerektirdiğinden burada elle sorguluyoruz
            $contact = Contact::where('company_id', $companyId)
                ->where('id', $data['contact_id'])
                ->lockForUpdate()
                ->first();

            if (! $contact) {
                throw ValidationException::withMessages([
                    'contact_id' => 'Seçilen cari bulunamadı.',
                ]);
            }

            if ($type === 'alis' && $contact->type !== 'tedarikci') {
                throw ValidationException::withMessages([
                    'contact_id' => 'Alış faturası yalnızca tedarikçi carilere kesilebilir. ("'.$contact->name.'" bir müşteri.)',
                ]);
            }
            if ($type === 'satis' && $contact->type !== 'musteri') {
                throw ValidationException::withMessages([
                    'contact_id' => 'Satış faturası yalnızca müşteri carilere kesilebilir. ("'.$contact->name.'" bir tedarikçi.)',
                ]);
            }

            $items = collect($data['items'])->filter(fn ($item) => $item['product_id'] && $item['quantity'] > 0);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Faturaya en az bir kalem eklemelisiniz.',
                ]);
            }

            // Aynı üründen birden fazla satır girilirse toplam tutarı geçmemesi için ürün bazında birleştir
            $merged = $items->groupBy('product_id')->map(fn ($rows) => [
                'product_id' => (int) $rows->first()['product_id'],
                'quantity' => $rows->sum(fn ($row) => (float) $row['quantity']),
                'unit_price' => isset($rows->first()['unit_price']) ? (float) $rows->first()['unit_price'] : null,
            ]);

            // Ürünleri kilitleyerek oku; eşzamanlı faturalarda stok yarışını engeller
            $products = Product::where('company_id', $companyId)
                ->whereIn('id', $merged->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Depo seçilmişse yükle ve kilitle
            $warehouse = null;
            if (! empty($data['warehouse_id'])) {
                $warehouse = \App\Models\Warehouse::where('company_id', $companyId)
                    ->where('id', $data['warehouse_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $warehouse) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => 'Seçilen depo bulunamadı veya pasif durumda.',
                    ]);
                }
            }

            $subtotal = 0;
            $lines = [];

            foreach ($merged as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "Seçilen ürün (ID: {$item['product_id']}) bulunamadı.",
                    ]);
                }

                // --- KRİTİK STOK KONTROLÜ (yalnızca satışta) ---
                if ($type === 'satis' && $product->stock_quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'Yetersiz stok! "%s" için istenen: %s %s, mevcut stok: %s %s.',
                            $product->name,
                            rtrim(rtrim(number_format($item['quantity'], 2, ',', '.'), '0'), ','),
                            $product->unit,
                            rtrim(rtrim(number_format($product->stock_quantity, 2, ',', '.'), '0'), ','),
                            $product->unit
                        ),
                    ]);
                }

                // --- DEPO STOK KONTROLÜ (satışta, depo seçilmişse) ---
                if ($type === 'satis' && $warehouse) {
                    $warehouseStock = $warehouse->stockOf($product->id);
                    if ($warehouseStock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => sprintf(
                                '%s deposunda "%s" için yetersiz stok! Mevcut: %s %s, istenen: %s %s. Farklı bir depo seçin veya önce transfer yapın.',
                                $warehouse->name,
                                $product->name,
                                $warehouseStock,
                                $product->unit,
                                rtrim(rtrim(number_format($item['quantity'], 2, ',', '.'), '0'), ','),
                                $product->unit
                            ),
                        ]);
                    }
                }

                // --- FİYAT YETKİSİ KONTROLÜ (yalnızca satışta) ---
                // Yönetici olmayan kullanıcılar, birim fiyatı ürünün alış-satış
                // aralığının dışına taşıyamaz; oturum yoksa (seeder/komut) atlanır.
                if ($type === 'satis' && auth()->check() && ! auth()->user()->isAdmin() && isset($item['unit_price'])) {
                    $min = (float) $product->purchase_price;
                    $max = (float) $product->sale_price;
                    $price = (float) $item['unit_price'];

                    if ($price < $min - 0.001 || $price > $max + 0.001) {
                        throw ValidationException::withMessages([
                            'items' => sprintf(
                                '"%s" için birim fiyat yetkinizin dışında (%s ₺). İzin verilen aralık: %s – %s ₺. Farklı bir fiyat için yöneticinize danışın.',
                                $product->name,
                                number_format($price, 2, ',', '.'),
                                number_format($min, 2, ',', '.'),
                                number_format($max, 2, ',', '.')
                            ),
                        ]);
                    }
                }

                // unit_price gönderilmediyse (API siparişi) ürünün satış fiyatı kullanılır
                $unitPrice = (float) ($item['unit_price'] ?? $product->sale_price);
                $lineTotal = round($item['quantity'] * $unitPrice, 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                $lines[] = [
                    'product' => $product,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $taxRate = (float) ($data['tax_rate'] ?? 0);
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $total = round($subtotal + $taxAmount, 2);

            // Faturayı önce oluştur, numarayı kaydın ID'sinden üret (tekil olması garantili)
            $invoice = Invoice::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouse?->id,
                'type' => $type,
                'contact_id' => $contact->id,
                'invoice_no' => 'TMP-'.uniqid(),
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $prefix = $type === 'satis' ? 'SF-' : 'AF-';
            $invoice->update([
                'invoice_no' => $prefix.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT),
                'verification_code' => strtoupper(substr(bin2hex(random_bytes(16)), 0, 24)),
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product_name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                $stockBefore = $line['product']->stock_quantity;

                if ($type === 'satis') {
                    // Satışta stok düşer
                    Product::where('id', $line['product']->id)->decrement('stock_quantity', $line['quantity']);
                    if ($warehouse) {
                        $warehouse->adjustStock($line['product']->id, -(int) $line['quantity']);
                    }
                    StockMovement::record(
                        $line['product'], 'satis', 'cikis',
                        (float) $line['quantity'],
                        (float) ($stockBefore - $line['quantity']),
                        $invoice, null, $warehouse?->id
                    );
                } else {
                    // Alışta stok artar
                    Product::where('id', $line['product']->id)->increment('stock_quantity', $line['quantity']);
                    if ($warehouse) {
                        $warehouse->adjustStock($line['product']->id, (int) $line['quantity']);
                    }
                    StockMovement::record(
                        $line['product'], 'alis', 'giris',
                        (float) $line['quantity'],
                        (float) ($stockBefore + $line['quantity']),
                        $invoice, null, $warehouse?->id
                    );
                }
            }

            // Cari bakiyesini fatura toplamı kadar GÜNCELLE (satışta artar, alışta azalır)
            if ($type === 'satis') {
                $contact->increment('balance', $total);
            } else {
                $contact->decrement('balance', $total);
            }

            DB::commit();

            return $invoice->fresh(['items.product', 'contact']);
        } catch (\Throwable $e) {
            // Stok yetersizliği dahil herhangi bir hata tüm işlemi geri alır
            DB::rollBack();

            throw $e;
        }
    }
}
