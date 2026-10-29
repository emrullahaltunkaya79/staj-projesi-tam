<?php

namespace App\Http\Controllers;

use App\Services\Backoffice\ApiClient;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    /**
     * CSPR ödeme ekranı: QR + adres + memo + geri sayım.
     */
    public function show(string $orderNo)
    {
        $payment = null;
        $error = null;

        try {
            $client = ApiClient::make();
            $result = $client->csprPayment($orderNo);
            $payment = $result['data'] ?? null;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        return view('shop.payment', [
            'orderNo' => $orderNo,
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
