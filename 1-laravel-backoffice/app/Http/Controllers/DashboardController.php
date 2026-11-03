<?php

namespace App\Http\Controllers;

use App\Models\CashTransfer;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ReceivableService;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            // Kart 1: Toplam Cari Sayısı
            'contact_count' => Contact::count(),
            'customer_count' => Contact::where('type', 'musteri')->count(),
            'supplier_count' => Contact::where('type', 'tedarikci')->count(),
            // Kart 2: Toplam Ürün Sayısı
            'product_count' => Product::count(),
            'stock_value' => (float) Product::selectRaw('SUM(stock_quantity * purchase_price) as total')->value('total') ?? 0,
            'reserved_quantity' => (int) Product::sum('reserved_quantity'),
            // Kart 3: Toplam Kesilen Fatura Tutarı (yalnızca satış faturaları)
            'total_sales' => (float) Invoice::where('type', 'satis')->sum('total'),
            'invoice_count' => Invoice::where('type', 'satis')->count(),
            'month_sales' => (float) Invoice::where('type', 'satis')
                ->whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->sum('total'),
        ];

        // Kasa durumu: nakit/havale tahsilatlar − tedarikçi ödemeleri − giderler ± virmanlar
        $cash = fn (string $type) => (float) Payment::where('direction', 'tahsilat')->where('payment_type', $type)->sum('amount')
            - (float) Payment::where('direction', 'odeme')->where('payment_type', $type)->sum('amount')
            - (float) Expense::where('payment_type', $type)->sum('amount');
        $stats['cash_nakit'] = $cash('nakit')
            + (float) CashTransfer::where('direction', 'havale_nakit')->sum('amount')
            - (float) CashTransfer::where('direction', 'nakit_havale')->sum('amount');
        $stats['cash_banka'] = $cash('havale')
            + (float) CashTransfer::where('direction', 'nakit_havale')->sum('amount')
            - (float) CashTransfer::where('direction', 'havale_nakit')->sum('amount');

        // Bu ay nakit akışı: tahsilat − gider
        $monthStart = now()->startOfMonth();
        $stats['month_collection'] = (float) Payment::where('direction', 'tahsilat')
            ->where('created_at', '>=', $monthStart)->sum('amount');
        $stats['month_expense'] = (float) Expense::where('expense_date', '>=', $monthStart->toDateString())->sum('amount');
        $stats['month_net'] = $stats['month_collection'] - $stats['month_expense'];

        // E-ticaret sipariş özeti
        $stats['orders_pending'] = Order::where('status', 'pending')->count();
        $stats['orders_pending_amount'] = (float) Order::where('status', 'pending')->sum('total');
        $stats['orders_awaiting_shipment'] = Order::whereIn('status', ['paid', 'preparing'])->count();
        $stats['orders_shipped'] = Order::where('status', 'shipped')->count();

        // Vadesi geçmiş faturalar (kalan tutarı olanlar)
        $overdue = app(ReceivableService::class)->overdueInvoices(auth()->user()->company_id);
        $overdueSummary = [
            'count' => $overdue->count(),
            'amount' => round($overdue->sum('remaining'), 2),
        ];

        return view('dashboard', [
            'stats' => $stats,
            'overdue' => $overdueSummary,
            // Son Hareketler: en son kesilen 5 fatura
            'recentInvoices' => Invoice::with('contact')->latest('id')->limit(5)->get(),
            // Son siparişler (e-ticaret)
            'recentOrders' => Order::latest('id')->limit(5)->get(),
            // Kritik Stok: stoğu 5'in altına düşen ürünler
            'lowStockProducts' => Product::where('stock_quantity', '<', 5)
                ->orderBy('stock_quantity')
                ->limit(6)
                ->get(),
        ]);
    }
}
