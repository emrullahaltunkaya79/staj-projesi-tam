<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\WebhookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * E-ticaret sipariş servisi — rezerve stok modeli.
 *
 * Yaşam döngüsü:
 *  1. create()        → sipariş gelir, stok REZERVE edilir (müsait azalır, fiziksel aynı)
 *  2. markPaid()      → ödeme onayı → rezerv kalıcı düşer + otomatik SATIŞ FATURASI kesilir
 *  3. cancel()        → sipariş iptal → rezerv serbest bırakılır (fiziksel stok hiç düşmedi)
 *
 * Tüm işlemler transaction + lockForUpdate altında; yarış koşulu yok.
 */
class OrderService
{
    public function __construct(private InvoiceService $invoiceService, private WebhookService $webhookService)
    {
    }

    /** Son create() çağrısında idempotent dalı mı çalıştı (mevcut sipariş mi döndü)? */
    public bool $lastCreateWasExisting = false;

    /**
     * Sipariş oluşturur ve stoğu rezerve eder.
     *
     * Aynı external_ref ile ikinci istek yeni sipariş açmaz; mevcut siparişi
     * döndürür (idempotent — e-ticaret tarafındaki yeniden denemeler çift
     * sipariş/çift rezerv yaratmaz).
     *
     * @throws ValidationException
     */
    public function create(int $companyId, array $data): Order
    {
        return DB::transaction(function () use ($companyId, $data) {
            if (! empty($data['external_ref'])) {
                $existing = Order::where('company_id', $companyId)
                    ->where('external_ref', $data['external_ref'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $this->lastCreateWasExisting = true;

                    return $existing;
                }
            }
            $this->lastCreateWasExisting = false;

            $contact = Contact::where('company_id', $companyId)
                ->where('id', $data['contact_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $items = collect($data['items'])
                ->filter(fn ($i) => ! empty($i['product_id']) && (float) ($i['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Sipariş en az bir kalem içermelidir.']);
            }

            // Ürünleri kilitle — rezerv yarışını önle
            $products = Product::where('company_id', $companyId)
                ->whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $snapshot = [];

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                if (! $product) {
                    throw ValidationException::withMessages(['items' => "Ürün bulunamadı: {$item['product_id']}"]);
                }

                $qty = (float) $item['quantity'];
                $available = $product->availableQuantity();

                // MÜSAİT stok kontrolü: fiziksel − rezerve
                if ($qty > $product->availableQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            '"%s" için yetersiz müsait stok! İstenen: %s, müsait: %s (fiziksel: %d, rezerve: %d).',
                            $product->name, $qty, $available, $product->stock_quantity, $product->reserved_quantity
                        ),
                    ]);
                }

                // Rezerv et
                $product->increment('reserved_quantity', $qty);

                $lineTotal = round($qty * (float) ($item['unit_price'] ?? $product->sale_price), 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                $snapshot[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => (float) ($item['unit_price'] ?? $product->sale_price),
                    'line_total' => $lineTotal,
                ];
            }

            $taxRate = (float) ($data['tax_rate'] ?? 20);
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $total = round($subtotal + $taxAmount, 2);

            $order = Order::create([
                'company_id' => $companyId,
                'contact_id' => $contact->id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'order_no' => 'TMP-'.uniqid(),
                'external_ref' => $data['external_ref'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_name' => $data['customer_name'] ?? $contact->name,
                'shipping_address' => $data['shipping_address'] ?? null,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'items' => $snapshot,
                'notes' => $data['notes'] ?? null,
                'expires_at' => now()->addMinutes(30), // ödeme zaman aşımı
            ]);

            $order->update(['order_no' => 'ORD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            return $order->fresh();
        });
    }

    /**
     * Ödeme onayı: rezervi kalıcı stoğa çevirir + otomatik satış faturası keser.
     *
     * @throws ValidationException
     */
    public function markPaid(Order $order, int $companyId): Order
    {
        return DB::transaction(function () use ($order, $companyId) {
            $order = Order::where('company_id', $companyId)->where('id', $order->id)->lockForUpdate()->first();

            if (! $order || $order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Yalnızca "Bekliyor" durumundaki siparişler ödenebilir.']);
            }

            $order->load('contact');

            // Rezerv → gerçek stok düşümü (InvoiceService satışı yapar)
            $invoice = $this->invoiceService->createSaleInvoice($companyId, [
                'contact_id' => $order->contact_id,
                'warehouse_id' => $order->warehouse_id,
                'issue_date' => now()->toDateString(),
                'tax_rate' => (float) $order->tax_rate,
                'notes' => "{$order->order_no} numaralı e-ticaret siparişinden kesilmiştir.",
                'items' => collect($order->items)->map(fn ($i) => [
                    'product_id' => $i['product_id'],
                    'quantity' => $i['quantity'],
                    'unit_price' => $i['unit_price'],
                ])->all(),
            ]);

            // Rezervi düş (stok artık gerçekten azaldı) — fatura kalemlerinden
            $invoice->load('items');
            foreach ($invoice->items as $item) {
                Product::where('id', $item->product_id)->decrement('reserved_quantity', $item->quantity);
            }

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'invoice_id' => $invoice->id,
            ]);

            // E-ticarete bildir
            $this->webhookService->orderPaid(
                $order->order_no,
                (string) $order->external_ref,
                (float) $order->total,
                $invoice->invoice_no,
                null
            );

            // Müşteriye onay e-postası
            if ($order->customer_email) {
                \Mail::to($order->customer_email)->queue(
                    new \App\Mail\OrderConfirmationMail($order->fresh())
                );
            }

            return $order->fresh();
        });
    }

    /**
     * Siparişi iptal eder: rezerv serbest bırakılır.
     */
    public function cancel(Order $order, int $companyId): Order
    {
        return DB::transaction(function () use ($order, $companyId) {
            $order = Order::where('company_id', $companyId)->where('id', $order->id)->lockForUpdate()->first();

            if (! $order || ! in_array($order->status, ['pending', 'paid'])) {
                throw ValidationException::withMessages(['order' => 'Bu durumda olan sipariş iptal edilemez.']);
            }

            // Eğer ödendiyse: fatura stoklarını geri al ve cari bakiyeyi düzelt
            if ($order->status === 'paid' && $order->invoice_id) {
                $order->load('invoice.items');

                foreach ($order->invoice->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
                    }
                }

                $order->contact->decrement('balance', $order->invoice->total);
            }

            // Pending ise: yalnızca rezervleri serbest bırak
            if ($order->status === 'pending') {
                foreach ($order->items as $item) {
                    Product::where('id', $item['product_id'])->decrement('reserved_quantity', $item['quantity']);
                }
            }

            $order->update(['status' => 'cancelled']);

            // E-ticarete bildir (sipariş iptal edildi — stok geri alındı)
            $this->webhookService->orderCancelled(
                $order->order_no,
                (string) ($order->external_ref ?? ''),
                $order->status === 'paid' ? 'Ödenmiş sipariş iptali — stok geri alındı' : 'Bekleyen sipariş iptali',
                $order->status === 'paid' ? (float) $order->total : 0
            );

            return $order->fresh();
        });
    }

    /**
     * Kargo bilgisi güncelleme ve statü geçişi (kargolandı/teslim).
     */
    public function updateShipping(Order $order, array $data, int $companyId): Order
    {
        return DB::transaction(function () use ($order, $data, $companyId) {
            $order = Order::where('company_id', $companyId)->where('id', $order->id)->lockForUpdate()->first();

            if (! $order) {
                throw ValidationException::withMessages(['order' => 'Sipariş bulunamadı.']);
            }

            if (! in_array($order->status, ['paid', 'preparing', 'shipped'])) {
                throw ValidationException::withMessages(['order' => 'Kargo bilgisi yalnızca ödendikten sonra eklenebilir.']);
            }

            $order->update([
                'tracking_no' => $data['tracking_no'],
                'carrier' => $data['carrier'],
                'status' => $data['status'] ?? $order->status,
            ]);

            // E-ticarete bildir: müşteri takip linkini görebilsin
            $this->webhookService->orderShipped(
                $order->order_no,
                (string) ($order->external_ref ?? ''),
                $data['carrier'],
                $data['tracking_no'],
                $order->trackingUrl() ?? ''
            );

            return $order->fresh();
        });
    }

    /**
     * Zaman aşımına uğrayan rezervasyonları serbest bırakır (scheduler çağırır).
     */
    public function releaseExpiredReservations(int $companyId): int
    {
        $expired = Order::where('company_id', $companyId)
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $released = 0;
        foreach ($expired as $order) {
            DB::transaction(function () use ($order, &$released) {
                foreach ($order->items as $item) {
                    Product::where('id', $item['product_id'])
                        ->decrement('reserved_quantity', $item['quantity']);
                }
                $order->update(['status' => 'cancelled', 'notes' => ($order->notes ? $order->notes.' — ' : '').'Ödeme zaman aşımı (otomatik iptal)']);
                $released++;
            });
        }

        return $released;
    }
}
