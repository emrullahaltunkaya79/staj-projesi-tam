<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ReceivableService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * ERP ana paneli: finansal operasyon + e-ticaret akışı tek ekranda.
     * Tüm hesaplamalar tek yerden; Blade yalnızca sunum yapar.
     */
    public function index()
    {
        $companyId = (int) auth()->user()->company_id;
        $bugun = now()->toDateString();
        $ayBasi = now()->startOfMonth()->toDateString();
        $oncekiAyBasi = now()->subMonth()->startOfMonth()->toDateString();
        $oncekiAySonu = now()->subMonth()->endOfMonth()->toDateString();
        $haftaBasi = now()->startOfWeek()->toDateString();

        // ============================================================
        // 1) CİRO — Fiziksel/B2B (siparişe bağlı olmayan satış faturası)
        //    + E-Ticaret (ödemesi gerçekleşmiş siparişler − iadeler)
        // ============================================================
        $faturaToOrder = Order::whereNotNull('invoice_id')->pluck('invoice_id');

        $aylikFiziksel = (float) Invoice::where('type', 'satis')
            ->whereBetween('issue_date', [$ayBasi, $bugun])
            ->when($faturaToOrder->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $faturaToOrder))
            ->sum('total');
        $oncekiAylikFiziksel = (float) Invoice::where('type', 'satis')
            ->whereBetween('issue_date', [$oncekiAyBasi, $oncekiAySonu])
            ->when($faturaToOrder->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $faturaToOrder))
            ->sum('total');

        $odemeAlmisDurumlar = ['paid', 'preparing', 'shipped', 'delivered'];
        $aylikEticaret = (float) Order::whereIn('status', $odemeAlmisDurumlar)
            ->whereBetween('created_at', [$ayBasi, now()])
            ->sum('total');
        $oncekiAylikEticaret = (float) Order::whereIn('status', $odemeAlmisDurumlar)
            ->whereBetween('created_at', [$oncekiAyBasi, $oncekiAySonu])
            ->sum('total');
        $aylikIade = (float) Order::whereIn('status', ['returned', 'return_requested'])
            ->whereBetween('created_at', [$ayBasi, now()])
            ->sum('iade_tutari');

        $aylikCiro = $aylikFiziksel + $aylikEticaret - $aylikIade;
        $oncekiAylikCiro = $oncekiAylikFiziksel + $oncekiAylikEticaret;
        $ciroDegisim = $oncekiAylikCiro > 0
            ? round((($aylikCiro - $oncekiAylikCiro) / $oncekiAylikCiro) * 100, 1)
            : null;

        // ============================================================
        // 2) E-TİCARET SİPARİŞ SAYAÇLARI (canlı)
        // ============================================================
        $siparisBekleyen = Order::where('status', 'pending')->count();
        $siparisBekleyenTutar = (float) Order::where('status', 'pending')->sum('total');
        $siparisOnayli = Order::whereIn('status', ['paid', 'preparing'])->count();
        $siparisOnayliTutar = (float) Order::whereIn('status', ['paid', 'preparing'])->sum('total');

        // ============================================================
        // 3) KARGO / SEVK — bugün ve bu hafta
        // ============================================================
        $sevkBugun = Order::whereNotNull('shipped_at')->whereDate('shipped_at', $bugun)->count();
        $sevkHafta = Order::whereNotNull('shipped_at')->whereDate('shipped_at', '>=', $haftaBasi)->count();
        $yolda = Order::where('status', 'shipped')->count();

        // ============================================================
        // 4) NET KÂR — ciro − ürün maliyeti − kargo maliyeti − komisyon
        //    (satış kalemleri bu ay; sipariş kaynaklı kargo/komisyon alanları)
        // ============================================================
        $aylikSatismaliyeti = (float) InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', 'satis')
            ->whereBetween('invoices.issue_date', [$ayBasi, $bugun])
            ->sum(DB::raw('invoice_items.quantity * COALESCE(products.purchase_price, 0)'));

        $kargoMaliyeti = (float) Order::whereIn('status', $odemeAlmisDurumlar)
            ->whereBetween('created_at', [$ayBasi, now()])
            ->sum('kargo_maliyeti');
        $komisyonlar = (float) Order::whereIn('status', $odemeAlmisDurumlar)
            ->whereBetween('created_at', [$ayBasi, now()])
            ->sum('pazaryeri_komisyonu');

        $aylikNetKar = $aylikCiro - $aylikSatismaliyeti - $kargoMaliyeti - $komisyonlar;
        $karMarji = $aylikCiro > 0 ? round(($aylikNetKar / $aylikCiro) * 100, 1) : null;

        // ============================================================
        // 5) VADESİ GEÇEN ALACAKLAR / BORÇLAR
        // ============================================================
        $overdue = app(ReceivableService::class)->overdueInvoices($companyId);
        $vadesiGecenAlacak = [
            'count' => $overdue->count(),
            'amount' => round((float) $overdue->sum('remaining'), 2),
        ];
        // Borçlar: bakiyesi pozitif tedarikçi carileri (bizim ödemesi beklenen)
        $vadesiGecenBorclar = (float) Contact::where('type', 'tedarikci')
            ->where('balance', '>', 0)
            ->sum('balance');

        // ============================================================
        // 6) GRAFİK VERİLERİ
        // ============================================================
        // 6a) Satış trendi — son 30 gün, günlük fiziksel vs e-ticaret
        $tarihler = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
        $siparisTrend = Order::whereIn('status', $odemeAlmisDurumlar)
            ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()])
            ->selectRaw('DATE(created_at) as gun, SUM(total) as tutar')
            ->groupBy('gun')->pluck('tutar', 'gun');
        $faturaTrend = Invoice::where('type', 'satis')
            ->whereBetween('issue_date', [now()->subDays(29)->toDateString(), $bugun])
            ->when($faturaToOrder->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $faturaToOrder))
            ->selectRaw('issue_date as gun, SUM(total) as tutar')
            ->groupBy('gun')->pluck('tutar', 'gun');

        $trendEtiket = $tarihler->map(fn ($d) => Carbon::parse($d)->format('d.m'))->values();
        $trendFiziksel = $tarihler->map(fn ($d) => round((float) ($faturaTrend[$d] ?? 0), 2))->values();
        $trendEticaret = $tarihler->map(fn ($d) => round((float) ($siparisTrend[Carbon::parse($d)->format('Y-m-d')] ?? 0), 2))->values();

        // 6b) Kargo/gönderi durumu dağılımı
        $durumDagilim = [
            'hazirlaniyor' => Order::whereIn('status', ['paid', 'preparing'])->count(),
            'kargoda' => Order::where('status', 'shipped')->count(),
            'teslim' => Order::where('status', 'delivered')->count(),
            'sorunlu' => Order::whereIn('status', ['return_requested', 'returned'])->count(),
        ];

        // 6c) En çok satan ürünler — bu ayın satış kalemleri (adet bazlı top 7)
        $enCokSatan = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', 'satis')
            ->whereBetween('invoices.issue_date', [$ayBasi, $bugun])
            ->groupBy('invoice_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(invoice_items.quantity)'))
            ->limit(7)
            ->get([
                'products.name as ad',
                DB::raw('SUM(invoice_items.quantity) as adet'),
                DB::raw('SUM(invoice_items.line_total) as tutar'),
            ]);

        // ============================================================
        // 7) TABLOLAR VE LİSTELER
        // ============================================================
        $sonSiparisler = Order::orderByDesc('id')->limit(8)->get();

        $kritikStok = Product::whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->orderBy('stock_quantity')
            ->limit(7)
            ->get(['id', 'name', 'stock_quantity', 'min_stock_level', 'sale_price', 'supplier_id']);

        // Kasa durumu (korunmuştur — önceki panelden)
        $kasa = fn (string $tip) => (float) Payment::where('direction', 'tahsilat')->where('payment_type', $tip)->sum('amount')
            - (float) Payment::where('direction', 'odeme')->where('payment_type', $tip)->sum('amount')
            - (float) Expense::where('payment_type', $tip)->sum('amount');
        $kasaNakit = $kasa('nakit')
            + (float) DB::table('cash_transfers')->where('direction', 'havale_nakit')->sum('amount')
            - (float) DB::table('cash_transfers')->where('direction', 'nakit_havale')->sum('amount');
        $kasaBanka = $kasa('havale')
            + (float) DB::table('cash_transfers')->where('direction', 'nakit_havale')->sum('amount')
            - (float) DB::table('cash_transfers')->where('direction', 'havale_nakit')->sum('amount');

        return view('dashboard', [

            'ciro' => [
                'aylik' => round($aylikCiro, 2),
                'fiziksel' => round($aylikFiziksel, 2),
                'eticaret' => round(max(0, $aylikEticaret - $aylikIade), 2),
                'degisim' => $ciroDegisim,
            ],
            'siparis' => [
                'bekleyen' => $siparisBekleyen,
                'bekleyen_tutar' => round($siparisBekleyenTutar, 2),
                'onayli' => $siparisOnayli,
                'onayli_tutar' => round($siparisOnayliTutar, 2),
            ],
            'kargo' => ['bugun' => $sevkBugun, 'hafta' => $sevkHafta, 'yolda' => $yolda],
            'netKar' => [
                'aylik' => round($aylikNetKar, 2),
                'marj' => $karMarji,
                'maliyet' => round($aylikSatismaliyeti, 2),
                'kargo' => round($kargoMaliyeti, 2),
                'komisyon' => round($komisyonlar, 2),
                'iade' => round($aylikIade, 2),
            ],
            'alacak' => $vadesiGecenAlacak,
            'borclar' => round($vadesiGecenBorclar, 2),
            'kasa' => ['nakit' => $kasaNakit, 'banka' => $kasaBanka],
            'grafik' => [
                'trendEtiket' => $trendEtiket,
                'trendFiziksel' => $trendFiziksel,
                'trendEticaret' => $trendEticaret,
                'durumDagilim' => $durumDagilim,
                'enCokSatan' => $enCokSatan,
            ],
            'sonSiparisler' => $sonSiparisler,
            'kritikStok' => $kritikStok,
            'overdue' => $vadesiGecenAlacak,
        ]);
    }

    /**
     * Canlı sayaç: panel açıkken 30 saniyede bir sipariş/sevk sayaçlarını tazeler.
     * GET /panel/ozet → JSON
     */
    public function ozet()
    {
        $bugun = now()->toDateString();
        $haftaBasi = now()->startOfWeek()->toDateString();

        return response()->json([
            'data' => [
                'bekleyen' => Order::where('status', 'pending')->count(),
                'bekleyen_tutar' => (float) Order::where('status', 'pending')->sum('total'),
                'onayli' => Order::whereIn('status', ['paid', 'preparing'])->count(),
                'sevk_bugun' => Order::whereNotNull('shipped_at')->whereDate('shipped_at', $bugun)->count(),
                'sevk_hafta' => Order::whereNotNull('shipped_at')->whereDate('shipped_at', '>=', $haftaBasi)->count(),
                'yolda' => Order::where('status', 'shipped')->count(),
            ],
        ]);
    }
}
