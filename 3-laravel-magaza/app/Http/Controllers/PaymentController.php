<?php

namespace App\Http\Controllers;

use App\Services\Backoffice\ApiClient;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    /**
     * CSPR ödeme ekranı: QR + adres + memo + geri sayım.
     * Erişim: siparişe özel anahtar (?k=) — OrderController'daki kapı ile aynı.
     */
    public function show(Request $request, string $orderNo)
    {
        $client = ApiClient::make();
        $k = (string) $request->query('k', '');

        try {
            $order = $client->order($orderNo)['data'] ?? [];
        } catch (RuntimeException $e) {
            abort(503, $e->getMessage());
        }

        abort_if(empty($order), 404, 'Sipariş bulunamadı.');

        // Erişim kapısı — try dışında: abort'un HttpException'ı catch'e yakalanmamalı
        $beklenen = (string) ($order['access_key'] ?? '');
        abort_if(
            $beklenen === '' || ! hash_equals($beklenen, $k),
            403,
            'Bu siparişin ödeme sayfasına erişim yetkiniz yok.',
        );

        $payment = null;
        $error = null;

        try {
            $result = $client->csprPayment($orderNo);
            $payment = $result['data'] ?? null;
            // 422 gibi durumlarda arka ofis gövdeyi mesajla döndürür (istisna fırlatmaz)
            $error = $payment === null ? ($result['message'] ?? 'Ödeme talimatı üretilemedi.') : null;
        } catch (RuntimeException $e) {
            $payment = null;
            $error = $e->getMessage();
        }

        return view('shop.payment', [
            'orderNo' => $orderNo,
            'k' => $k,
            'payment' => $payment,
            'error' => $error,
        ]);
    }

    /**
     * Canlı durum yoklaması (ödeme ekranından 5 sn'de bir çağrılır).
     */
    public function status(Request $request, string $orderNo)
    {
        $memo = (string) $request->query('memo', '');

        if ($memo === '') {
            return response()->json(['status' => 'unknown']);
        }

        try {
            $result = ApiClient::make()->csprStatus($memo);
            $status = $result['data']['status'] ?? 'unknown';
        } catch (RuntimeException $e) {
            $status = 'unknown';
        }

        return response()->json([
            'status' => $status,
            'paid' => in_array($status, ['confirmed', 'paid'], true),
        ]);
    }
}
