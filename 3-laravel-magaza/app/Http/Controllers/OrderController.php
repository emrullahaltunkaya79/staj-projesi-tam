<?php

namespace App\Http\Controllers;

use App\Services\Backoffice\ApiClient;
use Illuminate\Http\Request;
use RuntimeException;

class OrderController extends Controller
{
    /**
     * Erişim kapısı: sipariş numarası sıralı (ORD-000001…) olduğu için tek başına yetmez.
     * Siparişe özel erişim anahtarı (?k=) gerekir — anahtar yalnızca sipariş sahibine
     * (checkout yönlendirmesi / doğru e-postalı sorgulama) verilir.
     */
    private function erisimKontrol(Request $request, array $order): void
    {
        $anahtar = (string) $request->query('k', (string) $request->input('k', ''));
        $beklenen = (string) ($order['access_key'] ?? '');

        if ($beklenen !== '' && hash_equals($beklenen, $anahtar)) {
            return;
        }

        abort(403, 'Bu siparişi görüntüleme yetkiniz yok. Sipariş numarası ve e-posta ile sorgulayabilirsiniz.');
    }

    /**
     * Sipariş takip: durum, kalemeler, kargo linki.
     */
    public function show(Request $request, string $orderNo)
    {
        try {
            $order = ApiClient::make()->order($orderNo)['data'] ?? [];
        } catch (RuntimeException $e) {
            abort_if(true, 503, $e->getMessage());
        }

        abort_if(empty($order), 404, 'Sipariş bulunamadı.');
        $this->erisimKontrol($request, $order);

        return view('shop.order', ['order' => $order, 'k' => (string) $request->query('k', $request->input('k', ''))]);
    }

    /**
     * Ödeme onayı (test modu): arka ofiste fatura kesilir, stok düşer.
     */
    public function pay(Request $request, string $orderNo)
    {
        try {
            $order = ApiClient::make()->order($orderNo)['data'] ?? [];
        } catch (RuntimeException $e) {
            abort(503, $e->getMessage());
        }
        abort_if(empty($order), 404, 'Sipariş bulunamadı.');
        $this->erisimKontrol($request, $order);

        try {
            $result = ApiClient::make()->pay($orderNo);

            $mesaj = $result['message'] ?? 'Ödeme işlendi.';
            $fatura = $result['data']['invoice_no'] ?? null;

            return back()->with('success', $fatura ? "{$mesaj} (Fatura: {$fatura})" : $mesaj);
        } catch (RuntimeException $e) {
            $detay = $e->getMessage();
            if (str_contains($detay, '422')) {
                $detay = 'Sipariş bu durumda ödenebilir değil (süresi dolmuş veya zaten ödenmiş olabilir).';
            }

            return back()->with('error', $detay);
        }
    }

    /**
     * Sipariş iptali: rezerve stok geri bırakılır.
     */
    public function cancel(Request $request, string $orderNo)
    {
        try {
            $order = ApiClient::make()->order($orderNo)['data'] ?? [];
        } catch (RuntimeException $e) {
            abort(503, $e->getMessage());
        }
        abort_if(empty($order), 404, 'Sipariş bulunamadı.');
        $this->erisimKontrol($request, $order);

        try {
            $result = ApiClient::make()->cancel($orderNo, 'müşteri talebi (web)');

            return back()->with('success', $result['message'] ?? 'Sipariş iptal edildi.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
