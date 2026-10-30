<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CasperPayment;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * E-ticaret entegrasyonu API'si (Sanctum token ile).
 *
 * Tüm yanıtlar JSON; hata durumunda { "message": ..., "errors": ... } döner.
 */
class ApiController extends Controller
{
    /**
     * Ürün listesi: müsait stok bilgisiyle.
     * GET /api/v1/products
     */
    public function products(Request $request): JsonResponse
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->with('images')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'category' => $p->category?->name,
                'unit' => $p->unit,
                'sale_price' => (float) $p->sale_price,
                'discount_percent' => $p->discount_percent !== null ? (int) $p->discount_percent : null,
                'final_price' => $p->finalPrice(),
                'stock_quantity' => $p->stock_quantity,
                'reserved_quantity' => $p->reserved_quantity,
                'available_quantity' => $p->availableQuantity(),
                'in_stock' => $p->availableQuantity() > 0,
                'image_url' => $p->primaryImageUri(), // base64 data-URI (logo deseniyle aynı)
            ]);

        return response()->json(['data' => $products]);
    }

    /**
     * Tek ürün detayı.
     * GET /api/v1/products/{id}
     */
    public function productShow(int $id): JsonResponse
    {
        $product = Product::with('category', 'images')->find($id);

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'code' => $product->code,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category' => $product->category?->name,
                'unit' => $product->unit,
                'sale_price' => (float) $product->sale_price,
                'discount_percent' => $product->discount_percent !== null ? (int) $product->discount_percent : null,
                'final_price' => $product->finalPrice(),
                'purchase_price' => (float) $product->purchase_price,
                'stock_quantity' => $product->stock_quantity,
                'reserved_quantity' => $product->reserved_quantity,
                'available_quantity' => $product->availableQuantity(),
                'in_stock' => $product->availableQuantity() > 0,
                'image_url' => $product->primaryImageUri(),
            ],
        ]);
    }

    /**
     * Canlı stok sorgusu: müsait stok üzerinden.
     * GET /api/v1/stock
     */
    public function stock(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'barcode' => ['nullable', 'string', 'max:64'],
        ]);

        $query = Product::select('id', 'code', 'barcode', 'name', 'unit', 'stock_quantity', 'reserved_quantity');

        if ($ids = $request->query('product_ids')) {
            $query->whereIn('id', array_map('intval', (array) $ids));
        }

        // Barkod ile tek ürüne hızlı sorgu (POS/okuyucu senaryosu)
        if ($barcode = $request->query('barcode')) {
            $query->where('barcode', $barcode);
        }

        return response()->json([
            'checked_at' => now()->toIso8601String(),
            'data' => $query->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'stock_quantity' => $p->stock_quantity,
                'reserved_quantity' => $p->reserved_quantity,
                'available_quantity' => $p->availableQuantity(),
                'in_stock' => $p->availableQuantity() > 0,
            ]),
        ]);
    }

    /**
     * E-ticaret müşterisi = sistem carisi. Yoksa otomatik oluşturur.
     * POST /api/v1/customers
     */
    public function createCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'tax_number' => ['nullable', 'string', 'max:30'],
        ]);

        $contact = $validated['email']
            ? Contact::where('company_id', $request->user()->company_id)->where('email', $validated['email'])->first()
            : null;

        if (! $contact) {
            $contact = Contact::create([
                'company_id' => $request->user()->company_id,
                'type' => 'musteri',
                ...$validated,
            ]);
        }

        return response()->json([
            'message' => 'Müşteri hazır.',
            'data' => ['contact_id' => $contact->id, 'name' => $contact->name],
        ], $contact->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Sipariş oluşturma: stoğu REZERVE eder, ödeme gelince faturalaşır.
     * POST /api/v1/orders
     */
    public function createOrder(Request $request, OrderService $orderService): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'external_ref' => ['nullable', 'string', 'max:100'],
            'customer_email' => ['nullable', 'email'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'tax_rate' => ['required', 'numeric', 'in:0,1,10,20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $order = $orderService->create($request->user()->company_id, $validated);

        $mevcut = $orderService->lastCreateWasExisting;

        return response()->json([
            'message' => $mevcut
                ? 'Bu external_ref ile sipariş zaten mevcut — yeni sipariş açılmadı (idempotent).'
                : 'Sipariş alındı, stok rezerve edildi. Ödeme onayında faturalaşacak.',
            'data' => [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $order->status,
                'expires_at' => $order->expires_at?->toIso8601String(),
                'total' => (float) $order->total,
                'items' => $order->items,
            ],
        ], $mevcut ? 200 : 201);
    }

    /**
     * Sipariş listesi — e-ticaret "Siparişlerim" sayfası için.
     * GET /api/v1/orders?email=musteri@ornek.com&limit=20
     */
    public function ordersIndex(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['nullable', 'email'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = \App\Models\Order::with('invoice')
            ->where('company_id', $request->user()->company_id)
            ->orderByDesc('id');

        if ($email = $request->query('email')) {
            $query->where(function ($w) use ($email) {
                $w->where('customer_email', $email)
                    ->orWhereHas('contact', fn ($c) => $c->where('email', $email));
            });
        }

        $orders = $query->limit((int) ($request->query('limit') ?? 20))->get();

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'order_no' => $o->order_no,
                'status' => $o->status,
                'status_label' => $o->statusLabel(),
                'total' => (float) $o->total,
                'customer_name' => $o->customer_name,
                'invoice_no' => $o->invoice?->invoice_no,
                'created_at' => $o->created_at->toIso8601String(),
                'tracking_no' => $o->tracking_no,
                'tracking_url' => $o->trackingUrl(),
            ])->values(),
        ]);
    }

    /**
     * Sipariş detayı.
     * GET /api/v1/orders/{orderNo}
     */
    public function orderShow(Request $request, string $orderNo, OrderService $orderService): JsonResponse
    {
        $order = \App\Models\Order::where('company_id', $request->user()->company_id)
            ->where('order_no', $orderNo)
            ->with('invoice')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'order_no' => $order->order_no,
                'external_ref' => $order->external_ref,
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'total' => (float) $order->total,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'expires_at' => $order->expires_at?->toIso8601String(),
                'tracking_no' => $order->tracking_no,
                'carrier' => $order->carrier,
                'tracking_url' => $order->trackingUrl(),
                'invoice_no' => $order->invoice?->invoice_no,
                'items' => $order->items,
            ],
        ]);
    }

    /**
     * Ödeme webhook'u: ödeme sağlayıcısı onayında çağrılır → faturalaştır.
     * POST /api/v1/orders/{orderNo}/pay
     */
    public function pay(Request $request, string $orderNo, OrderService $orderService): JsonResponse
    {
        $order = \App\Models\Order::where('company_id', $request->user()->company_id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $orderService->markPaid($order, $request->user()->company_id);

        return response()->json([
            'message' => 'Ödeme işlendi, fatura kesildi.',
            'data' => [
                'order_no' => $order->order_no,
                'status' => $order->fresh()->status,
                'invoice_no' => $order->fresh()->invoice?->invoice_no,
            ],
        ]);
    }

    /**
     * Sipariş iptali: rezerv/fatura etkisini geri alır.
     * POST /api/v1/orders/{orderNo}/cancel
     */
    public function cancel(Request $request, string $orderNo, OrderService $orderService): JsonResponse
    {
        $order = \App\Models\Order::where('company_id', $request->user()->company_id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $orderService->cancel($order, $request->user()->company_id);

        return response()->json([
            'message' => 'Sipariş iptal edildi.',
            'data' => ['order_no' => $order->order_no, 'status' => $order->fresh()->status],
        ]);
    }

    /**
     * Kargo bilgisi güncelleme.
     * POST /api/v1/orders/{orderNo}/shipping
     */
    public function updateShipping(Request $request, string $orderNo, OrderService $orderService): JsonResponse
    {
        $order = \App\Models\Order::where('company_id', $request->user()->company_id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        $validated = $request->validate([
            'tracking_no' => ['required', 'string', 'max:50'],
            'carrier' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'in:shipped,delivered'],
        ]);

        $orderService->updateShipping($order, $validated, $request->user()->company_id);

        return response()->json([
            'message' => 'Kargo bilgisi güncellendi.',
            'data' => ['tracking_no' => $order->fresh()->tracking_no, 'carrier' => $order->fresh()->carrier],
        ]);
    }

    /**
     * Aktif API token'ları listesi (yönetim paneli için).
     */
    public function tokens(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->tokens()->get(['id', 'name', 'last_used_at', 'created_at']),
        ]);
    }

    /**
     * CSPR ödeme talimatı: adres + memo + tutar + QR.
     * E-ticaret sitesi bununla müşteriye ödeme ekranı gösterir;
     * ödeme zincirde görüldüğünde zamanlayıcı siparişi otomatik onaylar.
     * POST /api/v1/orders/{orderNo}/cspr-payment
     */
    public function csprPayment(Request $request, string $orderNo): JsonResponse
    {
        $order = \App\Models\Order::where('company_id', $request->user()->company_id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Yalnızca bekleyen siparişler için ödeme talimatı üretilebilir.'], 422);
        }

        $address = \App\Models\Setting::casperAddress();

        if (! $address) {
            return response()->json(['message' => 'CSPR alıcı adresi tanımlı değil — panelden Ayarlar → Entegrasyon\'a girin.'], 409);
        }

        $payment = app(\App\Services\CasperPaymentService::class)
            ->createPayment($order, $request->user()->company_id);

        $uri = 'casper:'.$address.'?amount='.$payment->amount_cspr.'&memo='.rawurlencode($payment->memo);
        $qr = (new \chillerlan\QRCode\QRCode(
            new \chillerlan\QRCode\QROptions(['scale' => 8, 'imageTransparent' => false])
        ))->render($uri);

        return response()->json(['data' => [
            'address' => $address,
            'memo' => $payment->memo,
            'amount_cspr' => (float) $payment->amount_cspr,
            'amount_try' => (float) $payment->amount_try,
            'network' => $payment->network,
            'rate' => (float) \App\Models\Setting::casperRate(),
            'expires_at' => $payment->expires_at->toIso8601String(),
            'payment_uri' => $uri,
            'qr_data_uri' => $qr,
        ]]);
    }

    /**
     * CSPR ödeme durumu (memo ile sorgu — e-ticaret polling).
     * GET /api/v1/cspr-payments/{memo}
     */
    public function csprStatus(Request $request, string $memo): JsonResponse
    {
        $payment = CasperPayment::where('company_id', $request->user()->company_id)
            ->where('memo', $memo)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'status' => $payment->status,
                'status_label' => $payment->statusLabel(),
                'amount_cspr' => (float) $payment->amount_cspr,
                'confirmed_at' => $payment->confirmed_at?->toIso8601String(),
                'order_no' => $payment->order->order_no,
                'invoice_no' => $payment->order->invoice?->invoice_no,
            ],
        ]);
    }
}
