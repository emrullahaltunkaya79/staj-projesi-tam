<?php

namespace App\Http\Controllers;

use App\Models\CasperPayment;
use App\Models\Order;
use App\Services\CasperPaymentService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CasperPaymentController extends Controller
{
    /**
     * Sipariş için CSPR ödeme başlatır (adres + memo + tutar).
     * POST /siparisler/{order}/cspr-odeme
     */
    public function start(Request $request, Order $order, CasperPaymentService $casper)
    {
        $companyId = $request->user()->company_id;

        if ($order->status !== 'pending') {
            return back()->with('error', 'CSPR ödemesi yalnızca bekleyen siparişler için başlatılabilir.');
        }

        $payment = $casper->createPayment($order, $companyId);

        return redirect()->route('siparisler.cspr.show', [$order, $payment->id]);
    }

    /**
     * CSPR ödeme ekranı: adres, memo, tutar, QR + durum.
     * GET /siparisler/{order}/cspr-odeme/{payment}
     */
    public function show(Request $request, Order $order, CasperPayment $payment)
    {
        $companyId = $request->user()->company_id;
        abort_unless($payment->company_id === $companyId && $payment->order_id === $order->id, 404);

        $address = \App\Models\Setting::casperAddress();
        $network = \App\Models\Setting::casperNetwork();

        return view('orders.cspr-payment', compact('order', 'payment', 'address', 'network'));
    }

    /**
     * Ödeme durumunu blok zincirden kontrol eder.
     * POST /siparisler/{order}/cspr-odeme/{payment}/kontrol
     */
    public function check(Request $request, Order $order, CasperPayment $payment, CasperPaymentService $casper)
    {
        $payment = $casper->checkOnChain($payment->fresh());

        if ($payment->status === 'confirmed') {
            // Ödeme onaylandı → siparişi faturalaştır (stok düşer, fatura kesilir)
            app(OrderService::class)->markPaid($order->fresh(), $request->user()->company_id);

            return redirect()->route('faturalar.show', $order->fresh()->invoice_id)
                ->with('success', 'CSPR ödemesi onaylandı — fatura kesildi!');
        }

        return redirect()->route('siparisler.cspr.show', [$order->id, $payment->id])
            ->with('info', 'Ödeme henüz blok zincirinde görülmedi. Birkaç dakika sonra tekrar kontrol edin.');
    }

    /**
     * Geliştirme/test: elle onayla.
     */
    public function forceConfirm(Request $request, Order $order, CasperPayment $payment, CasperPaymentService $casper)
    {
        $casper->forceConfirm($payment->fresh());
        app(OrderService::class)->markPaid($order->fresh(), $request->user()->company_id);

        return redirect()->route('faturalar.show', $order->fresh()->invoice_id)
            ->with('success', '[TEST] CSPR ödemesi elle onaylandı — fatura kesildi.');
    }

    /**
     * API: ödeme durumu JSON (e-ticaret polling).
     * GET /api/v1/cspr-payments/{memo}
     */
    public function apiStatus(string $memo): JsonResponse
    {
        $payment = CasperPayment::where('memo', $memo)->first();

        if (! $payment) {
            return response()->json(['message' => 'Ödeme bulunamadı.'], 404);
        }

        return response()->json([
            'data' => [
                'memo' => $payment->memo,
                'status' => $payment->status,
                'amount_cspr' => (float) $payment->amount_cspr,
                'amount_try' => (float) $payment->amount_try,
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'deploy_hash' => $payment->deploy_hash,
            ],
        ]);
    }
}
