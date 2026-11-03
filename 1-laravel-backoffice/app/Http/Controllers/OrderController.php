<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('contact')->latest('id');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'filters' => ['status' => $status],
        ]);
    }

    public function show(Order $order)
    {
        $order->load('contact', 'warehouse', 'invoice');

        return view('orders.show', compact('order'));
    }

    /**
     * Kargo kutu etiketi (PDF): alıcı/gönderici adresli teslim etiketi.
     */
    public function label(Request $request, Order $order)
    {
        $order->load('contact', 'warehouse');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('orders.label', [
            'order' => $order,
            'company' => $request->user()->company,
        ])->setPaper([0, 0, 190, 290], 'portrait'); // ~100×153mm etiket

        return $pdf->download('etiket-' . $order->order_no . '.pdf');
    }

    /**
     * Panel içinden statü değiştirme (admin).
     */
    public function markPaid(Request $request, Order $order, OrderService $orderService)
    {
        try {
            $orderService->markPaid($order, $request->user()->company_id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Ödeme işlendi: fatura kesildi ve stoklar düşürüldü.');
    }

    /**
     * Panel: sipariş iptali.
     */
    public function cancelOrder(Request $request, Order $order, OrderService $orderService)
    {
        try {
            $orderService->cancel($order, $request->user()->company_id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Sipariş iptal edildi.');
    }

    /**
     * Panel: kargo bilgisi + kargolama.
     */
    public function updateShipping(Request $request, Order $order, OrderService $orderService)
    {
        $validated = $request->validate([
            'tracking_no' => ['required', 'string', 'max:50'],
            'carrier' => ['required', 'string', 'max:50'],
        ]);

        $orderService->updateShipping($order, array_merge($validated, ['status' => 'shipped']), $request->user()->company_id);

        return back()->with('success', 'Kargo bilgileri kaydedildi: '.$order->fresh()->statusLabel());
    }
}
