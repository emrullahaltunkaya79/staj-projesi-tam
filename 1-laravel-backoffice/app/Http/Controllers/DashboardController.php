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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** Dönem filtresi seçenekleri → [başlangıç ofseti, süre] çözümlemesi index/veriler içinde yapılır */
    public const DONEMLER = ['bugun', 'hafta', 'ay', '30gun', 'ozel'];

    /**
     * ERP ana paneli. KPI'lar seçili döneme göre hesaplanır (varsayılan: bu ay),
     * satış trendi her zaman son 30 günün karşılaştırmalı akışını gösterir.
     */
    public function index(Request $request)
    {
        $companyId = (int) auth()->user()->company_id;
        [$bas, $son] = $this->donemAraligi($request);
        $veri = $this->panelVerisi($bas, $son, $companyId);

        $trend = $this->trendVerisi(30);
        $durumDagilim = $this->durumDagilimi();

        $veri['grafik'] = [
            'trendEtiket' => $trend['etiket'],
            'trendFiziksel' => $trend['fiziksel'],
            'trendEticaret' => $trend['eticaret'],
            'durumDagilim' => $durumDagilim,
            'enCokSatan' => $this->enCokSatan($bas->toDateString(), $son->toDateString()),
        ];

        $overdue = app(ReceivableService::class)->overdueInvoices($companyId);

        return view('dashboard', [
            'veri' => $veri,
            'grafik' => $veri['grafik'],
            'sonSiparisler' => Order::orderByDesc('id')->limit(8)->get(),
            'kritikStok' => Product::whereColumn('stock_quantity', '<=', 'min_stock_level')
                ->orderBy('stock_quantity')
                ->limit(7)
                ->get(['id', 'name', 'stock_quantity', 'min_stock_level', 'sale_price', 'supplier_id']),
            'overdue' => [
                'count' => $overdue->count(),
                'amount' => round((float) $overdue->sum('remaining'), 2),
            ],
        ]);
    }

    /**
     * Dönem filtresi ucu — Alpine bileşeni tüm kart verilerini bu JSON'dan günceller.
     * GET /panel/veriler?donem=bugun|hafta|ay|30gun|ozel&bas=&son=
     */
    public function veriler(Request $request): JsonResponse
    {
        $companyId = (int) auth()->user()->company_id;
        [$bas, $son] = $this->donemAraligi($request);
        $veri = $this->panelVerisi($bas, $son, $companyId);

        $veri['grafik'] = [
            'trendEtiket' => $this->trendVerisi(30)['etiket'],
            'trendFiziksel' => $this->trendVerisi(30)['fiziksel'],
            'trendEticaret' => $this->trendVerisi(30)['eticaret'],
            'durumDagilim' => $this->durumDagilimi(),
            'enCokSatan' => $this->enCokSatan($bas->toDateString(), $son->toDateString()),
        ];

        return response()->json(['data' => $veri]);
    }

    /** Dönem parametresini Carbon aralığına çözer (varsayılan: bu ay). */
    private function donemAraligi(Request $request): array
    {
        $donem = $request->query('donem', 'ay');

        return match ($donem) {
            'bugun' => [now()->startOfDay(), now()],
            'hafta' => [now()->startOfWeek(), now()],
            '30gun' => [now()->subDays(29)->startOfDay(), now()],
            'ozel' => [
                Carbon::parse($request->query('bas', now()->startOfMonth()))->startOfDay(),
                Carbon::parse($request->query('son', now()))->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()],
        };
    }

    /**
     * Canlı sayaç ucu — panel açıkken 30 saniyede bir sipariş/sevk sayaçlarını tazeler.
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

    /**
     * Seçili dönem için tüm KPI bloklarını hesaplar. Kısa/tam biçimler sunucuda
     * üretilir — Blade yalnızca string basar, Alpine döneme göre yeniden alır.
     */
    private function panelVerisi(Carbon $bas, Carbon $son, int $companyId): array
    {
        $basD = $bas->toDateString();
        $sonD = $son->toDateString();
        $gunSayisi = max(1, (int) ceil($bas->diffInDays($son) + 1));

        // Önceki eşit uzunluklu pencere (yüzdesel karşılaştırma)
        $oncekiBas = $bas->copy()->subDays($gunSayisi);
        $oncekiSon = $bas->copy()->subDay();

        // ---------- CİRO ----------
        $faturaToOrder = Order::whereNotNull('invoice_id')->pluck('invoice_id');

        $ciroDonem = fn (Carbon $b, Carbon $s) => [
            'fiziksel' => (float) Invoice::where('type', 'satis')
                ->whereBetween('issue_date', [$b->toDateString(), $s->toDateString()])
                ->when($faturaToOrder->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $faturaToOrder))
                ->sum('total'),
            'eticaret' => (float) Order::whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])
                ->whereBetween('created_at', [$b, $s])
                ->sum('total'),
        ];

        $donem = $ciroDonem($bas, $son);
        $onceki = $ciroDonem($oncekiBas, $oncekiSon);
        $iade = (float) Order::whereIn('status', ['returned', 'return_requested'])
            ->whereBetween('created_at', [$bas, $son])
            ->sum('iade_tutari');

        $ciroNet = $donem['fiziksel'] + $donem['eticaret'] - $iade;
        $oncekiCiro = $onceki['fiziksel'] + $onceki['eticaret'];
        $degisim = $oncekiCiro > 0 ? round((($ciroNet - $oncekiCiro) / $oncekiCiro) * 100, 1) : null;

        // ---------- SİPARİŞ ----------
        $bekleyen = Order::where('status', 'pending')->count();
        $onayli = Order::whereIn('status', ['paid', 'preparing'])->count();

        // ---------- KARGO ----------
        $sevkBugun = Order::whereNotNull('shipped_at')->whereDate('shipped_at', today())->count();
        $sevkHafta = Order::whereNotNull('shipped_at')->whereDate('shipped_at', '>=', now()->startOfWeek()->toDateString())->count();
        $yolda = Order::where('status', 'shipped')->count();

        // ---------- NET KÂR ----------
        $aylikSatismaliyeti = (float) InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', 'satis')
            ->whereBetween('invoices.issue_date', [$basD, $sonD])
            ->sum(DB::raw('invoice_items.quantity * COALESCE(products.purchase_price, 0)'));

        $kargoMaliyeti = (float) Order::whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$bas, $son])
            ->sum('kargo_maliyeti');
        $komisyonlar = (float) Order::whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$bas, $son])
            ->sum('pazaryeri_komisyonu');

        $netKar = $ciroNet - $aylikSatismaliyeti - $kargoMaliyeti - $komisyonlar;
        $marj = $ciroNet > 0 ? round(($netKar / $ciroNet) * 100, 1) : null;

        // ---------- VADESİ GEÇEN / BORÇ / KASA ----------
        $overdue = app(ReceivableService::class)->overdueInvoices($companyId);
        $borclar = (float) Contact::where('type', 'tedarikci')->where('balance', '>', 0)->sum('balance');

        // Personel görünümü için operasyonel sayaçlar
        $bekleyenIade = Order::where('status', 'return_requested')->count();
        $kritikStokAdet = Product::whereColumn('stock_quantity', '<=', 'min_stock_level')->count();

        $kasa = fn (string $tip) => (float) Payment::where('direction', 'tahsilat')->where('payment_type', $tip)->sum('amount')
            - (float) Payment::where('direction', 'odeme')->where('payment_type', $tip)->sum('amount')
            - (float) Expense::where('payment_type', $tip)->sum('amount');
        $kasaNakit = $kasa('nakit')
            + (float) DB::table('cash_transfers')->where('direction', 'havale_nakit')->sum('amount')
            - (float) DB::table('cash_transfers')->where('direction', 'nakit_havale')->sum('amount');
        $kasaBanka = $kasa('havale')
            + (float) DB::table('cash_transfers')->where('direction', 'nakit_havale')->sum('amount')
            - (float) DB::table('cash_transfers')->where('direction', 'havale_nakit')->sum('amount');

        $tahsilat = (float) Payment::where('direction', 'tahsilat')->where('created_at', '>=', $bas)->sum('amount');
        $gider = (float) Expense::where('expense_date', '>=', $basD)->sum('amount');

        // ---------- KISA/TAM BİÇİMLEYİCİ ----------
        $kisa = function (float $v) {
            $isaret = $v < 0 ? '-' : '';
            $v = abs($v);
            if ($v >= 1_000_000) {
                return ['n' => $isaret.rtrim(rtrim(number_format($v / 1_000_000, 1, ',', '.'), '0'), ','), 'ek' => 'M ₺'];
            }
            if ($v >= 1_000) {
                return ['n' => $isaret.rtrim(rtrim(number_format($v / 1_000, 1, ',', '.'), '0'), ','), 'ek' => 'B ₺'];
            }
            return ['n' => $isaret.number_format($v, 0, ',', '.'), 'ek' => '₺'];
        };
        $fmt = fn (float $v) => number_format($v, 2, ',', '.').' ₺';
        $kisaCift = fn (float $v) => ['kisa' => $kisa($v), 'tam' => $fmt($v)];

        $degisimMetin = $degisim === null
            ? 'Önceki dönem verisi yok'
            : (($degisim >= 0 ? '▲ ' : '▼ ').'%'.abs($degisim).' önceki döneme göre');
        $degisimTur = $degisim === null ? 'notr' : ($degisim >= 0 ? 'iyi' : 'kotu');

        $marjTur = $marj === null ? 'notr' : ($marj >= 10 ? 'iyi' : ($marj >= 0 ? 'uyari' : 'kotu'));

        return [
            'donem' => ['bas' => $bas->toDateString(), 'son' => $sonD, 'gun' => $gunSayisi],
            'ciro' => [
                'kisa' => $kisa($ciroNet),
                'tam' => $fmt($ciroNet),
                'fiziksel_kisa' => $kisa($donem['fiziksel'])['n'],
                'fiziksel_tam' => $fmt($donem['fiziksel']),
                'eticaret_kisa' => $kisa(max(0, $donem['eticaret'] - $iade))['n'],
                'eticaret_tam' => $fmt(max(0, $donem['eticaret'] - $iade)),
                'degisim' => $degisim,
                'degisim_metin' => $degisimMetin,
                'degisim_tur' => $degisimTur,
            ],
            'siparis' => [
                'bekleyen' => $bekleyen,
                'bekleyen_tutar_fmt' => $fmt($bekleyen ? (float) Order::where('status', 'pending')->sum('total') : 0),
                'onayli' => Order::whereIn('status', ['paid', 'preparing'])->count(),
                'onayli_tutar_fmt' => $fmt((float) Order::whereIn('status', ['paid', 'preparing'])->sum('total')),
            ],
            'kargo' => [
                'bugun' => $sevkBugun,
                'hafta' => $sevkHafta,
                'yolda' => $yolda,
            ],
            'net_kar' => [
                'kisa' => $kisa($netKar),
                'tam' => $fmt($netKar),
                'marj' => $marj,
                'marj_tur' => $marjTur,
                'maliyet_fmt' => $fmt($aylikSatismaliyeti),
                'kargo_fmt' => $fmt($kargoMaliyeti),
                'komisyon_fmt' => $fmt($komisyonlar),
                'iade_fmt' => $fmt($iade),
            ],
            'alacak' => [
                'adet' => $overdue->count(),
                'kisa' => $kisa((float) $overdue->sum('remaining')),
                'tam' => $fmt((float) $overdue->sum('remaining')),
                'borclar_fmt' => $fmt($borclar),
                'kritik_stok' => Product::whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
                'bekleyen_iade' => Order::where('status', 'return_requested')->count(),
            ],
            'kasa' => [
                'nakit_kisa' => $kisa($kasaNakit)['n'],
                'nakit_ek' => $kisa($kasaNakit)['ek'],
                'nakit_tam' => $fmt($kasaNakit),
                'banka_kisa' => $kisa($kasaBanka)['n'],
                'banka_ek' => $kisa($kasaBanka)['ek'],
                'banka_tam' => $fmt($kasaBanka),
            ],
            'nakit_akisi' => ['tahsilat_fmt' => $fmt($tahsilat), 'gider_fmt' => $fmt($gider), 'net_fmt' => ($tahsilat - $gider >= 0 ? '+' : '').$fmt($tahsilat - $gider), 'net' => round($tahsilat - $gider, 2)],
            'iade' => round($iade, 2),
            'operasyonel' => [
                'bekleyen_iade' => Order::where('status', 'return_requested')->count(),
                'kritik_stok' => Product::whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
            ],
        ];
    }

    /** Son N günlük (fiziksel vs e-ticaret) satış trendi. */
    private function trendVerisi(int $gun): array
    {
        $faturaToOrder = Order::whereNotNull('invoice_id')->pluck('invoice_id');
        $tarihler = collect(range($gun - 1, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

        $siparisTrend = Order::whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [now()->subDays($gun - 1)->startOfDay(), now()])
            ->selectRaw('DATE(created_at) as gun, SUM(total) as tutar')
            ->groupBy('gun')->pluck('tutar', 'gun');
        $faturaTrend = Invoice::where('type', 'satis')
            ->whereBetween('issue_date', [now()->subDays($gun - 1)->toDateString(), today()])
            ->when($faturaToOrder->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $faturaToOrder))
            ->selectRaw('issue_date as gun, SUM(total) as tutar')
            ->groupBy('gun')->pluck('tutar', 'gun');

        return [
            'etiket' => $tarihler->map(fn ($d) => Carbon::parse($d)->format('d.m'))->values(),
            'fiziksel' => $tarihler->map(fn ($d) => round((float) ($faturaTrend[$d] ?? 0), 2))->values(),
            'eticaret' => $tarihler->map(fn ($d) => round((float) ($siparisTrend[Carbon::parse($d)->format('Y-m-d')] ?? 0), 2))->values(),
        ];
    }

    /** Gönderi durum dağılımı (anlık). */
    private function durumDagilimi(): array
    {
        return [
            'hazirlaniyor' => Order::whereIn('status', ['paid', 'preparing'])->count(),
            'kargoda' => Order::where('status', 'shipped')->count(),
            'teslim' => Order::where('status', 'delivered')->count(),
            'sorunlu' => Order::whereIn('status', ['return_requested', 'returned'])->count(),
        ];
    }

    /** Dönemin en çok satan ürünleri (adet bazlı top 7). */
    private function enCokSatan(string $bas, string $son): array
    {
        return InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', 'satis')
            ->whereBetween('invoices.issue_date', [$bas, $son])
            ->groupBy('invoice_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(invoice_items.quantity)'))
            ->limit(7)
            ->get([
                'products.name as ad',
                DB::raw('SUM(invoice_items.quantity) as adet'),
                DB::raw('SUM(invoice_items.line_total) as tutar'),
            ])
            ->map(fn ($r) => ['ad' => $r->ad, 'adet' => (int) $r->adet, 'tutar' => (float) $r->tutar])
            ->all();
    }
}
