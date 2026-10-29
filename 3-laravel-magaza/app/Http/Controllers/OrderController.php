<?php

namespace App\Http\Controllers;

use App\Services\Backoffice\ApiClient;
use Illuminate\Http\Request;
use RuntimeException;

class OrderController extends Controller
{
    /**
     * Sipariş takip: durum, kalemeler, kargo linki.
     */
    public function show(string $orderNo)
    {
        try {
            $order = ApiClient::make()->order($orderNo)['data'] ?? [];
        } catch (RuntimeException $e) {
            abort_if(true, 503, $e->getMessage());
        }

        abort_if(empty($order), 404, 'Sipariş bulunamadı.');

        return view('shop.order', ['order' => $order]);
    }

    /**
     * Ödeme onayı (test modu): arka ofiste fatura kesilir, stok düşer.
     */
    public function pay(Request $request, string $orderNo)
    {
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
            $result = ApiClient::make()->cancel($orderNo, 'müşteri talebi (web)');

            return back()->with('success', $result['message'] ?? 'Sipariş iptal edildi.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
