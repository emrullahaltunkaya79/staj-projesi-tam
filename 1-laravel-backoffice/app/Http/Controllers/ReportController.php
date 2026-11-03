<?php

namespace App\Http\Controllers;

use App\Models\CashTransfer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ReceivableService;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // Son 12 ayın satış/alış dökümü — aylık seriler SQL içinde toplanır
        // (substr(tarih,1,7) hem MySQL hem SQLite'ta çalışır)
        $startDate = now()->subMonths(11)->startOfMonth();

        // Aylık seriler SQL içinde toplanır (substr(tarih,1,7) hem MySQL hem SQLite'ta çalışır)
        $monthlyInvoices = fn (string $type) => Invoice::where('type', $type)
            ->where('issue_date', '>=', $startDate->toDateString())
            ->groupBy(DB::raw('substr(issue_date, 1, 7)'))
            ->selectRaw('substr(issue_date, 1, 7) as ym, SUM(total) as total')
            ->pluck('total', 'ym');

        $salesByMonth = $monthlyInvoices('satis')->all();
        $purchasesByMonth = $monthlyInvoices('alis')->all();

        $expensesByMonth = Expense::where('expense_date', '>=', $startDate->toDateString())
            ->groupBy(DB::raw('substr(expense_date, 1, 7)'))
            ->selectRaw('substr(expense_date, 1, 7) as ym, SUM(amount) as total')
            ->pluck('total', 'ym')
            ->all();

        $labels = [];
        $cursor = $startDate->copy();
        while ($cursor->lessThanOrEqualTo(now()->endOfMonth())) {
            $key = $cursor->format('Y-m');
            $labels[$key] = $cursor->translatedFormat('M y');
            $cursor->addMonth();
        }
        $labelKeys = array_keys($labels);
        $emptyMonths = array_fill_keys($labelKeys, 0.0);
        $salesByMonth = array_map(fn ($k) => round((float) ($salesByMonth[$k] ?? 0), 2), $emptyMonths);
        $purchasesByMonth = array_map(fn ($k) => round((float) ($purchasesByMonth[$k] ?? 0), 2), $emptyMonths);
        $expensesByMonth = array_map(fn ($k) => round((float) ($expensesByMonth[$k] ?? 0), 2), $emptyMonths);

        // En çok satan 5 ürün (satılan adet bazında, SQL toplaması)
        $topProducts = InvoiceItem::whereHas('invoice', fn ($q) => $q->where('type', 'satis'))
            ->groupBy('product_name')
            ->selectRaw('product_name, SUM(quantity) as qty')
            ->orderByDesc('qty')
            ->limit(5)
            ->pluck('qty', 'product_name')
            ->all();

        // Özet kartları
        $salesNet = (float) Invoice::where('type', 'satis')->sum('subtotal');
        $purchasesNet = (float) Invoice::where('type', 'alis')->sum('subtotal');
        $stats = [
            'sales_total' => (float) Invoice::where('type', 'satis')->sum('total'),
            'purchase_total' => (float) Invoice::where('type', 'alis')->sum('total'),
            'gross_profit' => $salesNet - $purchasesNet,
            'collection_total' => (float) Payment::where('direction', 'tahsilat')->sum('amount'),
            'expense_total' => (float) Expense::sum('amount'),
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

        // Ürün bazında kâr marjı: satış fiyatı − alış fiyatı (stoktaki ürünler)
        $profitMargins = Product::selectRaw('name, sale_price - purchase_price as margin, ROUND((sale_price - purchase_price) / NULLIF(purchase_price, 0) * 100, 1) as pct')
            ->whereNotNull('sale_price')
            ->orderByDesc('margin')
            ->limit(10)
            ->get();

        // En çok kâr getiren ürünler (satılan miktar × birim kâr)
        $profitByProduct = InvoiceItem::whereHas('invoice', fn ($q) => $q->where('type', 'satis'))
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->groupBy('invoice_items.product_name')
            ->selectRaw('invoice_items.product_name, SUM(invoice_items.quantity * (invoice_items.unit_price - products.purchase_price)) as total_profit, SUM(invoice_items.quantity) as qty')
            ->orderByDesc('total_profit')
            ->limit(5)
            ->get();

        // Alacak yaşlandırma kovaları
        $aging = app(ReceivableService::class)->agingBuckets(auth()->user()->company_id);

        return view('reports.index', [
            'stats' => $stats,
            'aging' => $aging,
            'chart' => [
                'labels' => array_values($labels),
                'sales' => array_values($salesByMonth),
                'purchases' => array_values($purchasesByMonth),
                'expenses' => array_values($expensesByMonth),
            ],
            'topProducts' => [
                'labels' => array_keys($topProducts),
                'quantities' => array_map('floatval', array_values($topProducts)),
            ],
            'profitMargins' => $profitMargins,
            'profitByProduct' => $profitByProduct,
        ]);
    }

    /**
     * Rapor sayfasını çok sayfalı Excel olarak indirir.
     */
    public function export()
    {
        $stats = $this->statsForExport();

        $margins = Product::selectRaw('name, code, sale_price, purchase_price, sale_price - purchase_price as margin, ROUND((sale_price - purchase_price) / NULLIF(purchase_price, 0) * 100, 1) as pct')
            ->whereNotNull('sale_price')
            ->orderByDesc('margin')
            ->get();

        $profitByProduct = InvoiceItem::whereHas('invoice', fn ($q) => $q->where('type', 'satis'))
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->groupBy('invoice_items.product_name')
            ->selectRaw('invoice_items.product_name, SUM(invoice_items.quantity * (invoice_items.unit_price - products.purchase_price)) as total_profit, SUM(invoice_items.quantity) as qty')
            ->orderByDesc('total_profit')
            ->get();

        $export = new class($stats, $margins, $profitByProduct) implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
            public function __construct(private array $stats, private $margins, private $profitByProduct) {}

            public function sheets(): array
            {
                return [
                    'Özet' => new class($this->stats) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                        public function __construct(private array $stats) {}
                        public function headings(): array
                        {
                            return ['Gösterge', 'Değer'];
                        }
                        public function array(): array
                        {
                            $fmt = fn ($v) => round((float) $v, 2);
                            return [
                                ['Toplam Satış', $fmt($this->stats['sales_total'])],
                                ['Toplam Alış', $fmt($this->stats['purchase_total'])],
                                ['Brüt Kâr', $fmt($this->stats['gross_profit'])],
                                ['Toplam Tahsilat', $fmt($this->stats['collection_total'])],
                                ['Toplam Gider', $fmt($this->stats['expense_total'])],
                                ['Kasa — Nakit', $fmt($this->stats['cash_nakit'])],
                                ['Kasa — Banka', $fmt($this->stats['cash_banka'])],
                            ];
                        }
                    },
                    'Ürün Kâr Marjı' => new class($this->margins) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                        public function __construct(private $margins) {}
                        public function headings(): array
                        {
                            return ['Ürün', 'Kod', 'Satış Fiyatı', 'Alış Fiyatı', 'Birim Kâr', 'Marj %'];
                        }
                        public function array(): array
                        {
                            return $this->margins->map(fn ($p) => [
                                $p->name, $p->code, (float) $p->sale_price, (float) $p->purchase_price,
                                round((float) $p->margin, 2), (float) $p->pct,
                            ])->all();
                        }
                    },
                    'En Çok Kâr Getirenler' => new class($this->profitByProduct) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                        public function __construct(private $rows) {}
                        public function headings(): array
                        {
                            return ['Ürün', 'Satılan Miktar', 'Toplam Kâr'];
                        }
                        public function array(): array
                        {
                            return $this->rows->map(fn ($r) => [
                                $r->product_name, (float) $r->qty, round((float) $r->total_profit, 2),
                            ])->all();
                        }
                    },
                ];
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download(
            $export,
            'raporlar-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    /**
     * Excel özeti için temel göstergeler (index ile aynı kaynaklar).
     */
    private function statsForExport(): array
    {
        $salesNet = (float) Invoice::where('type', 'satis')->sum('subtotal');
        $purchasesNet = (float) Invoice::where('type', 'alis')->sum('subtotal');

        $cash = fn (string $type) => (float) Payment::where('direction', 'tahsilat')->where('payment_type', $type)->sum('amount')
            - (float) Payment::where('direction', 'odeme')->where('payment_type', $type)->sum('amount')
            - (float) Expense::where('payment_type', $type)->sum('amount');

        return [
            'sales_total' => (float) Invoice::where('type', 'satis')->sum('total'),
            'purchase_total' => (float) Invoice::where('type', 'alis')->sum('total'),
            'gross_profit' => $salesNet - $purchasesNet,
            'collection_total' => (float) Payment::where('direction', 'tahsilat')->sum('amount'),
            'expense_total' => (float) Expense::sum('amount'),
            'cash_nakit' => $cash('nakit')
                + (float) CashTransfer::where('direction', 'havale_nakit')->sum('amount')
                - (float) CashTransfer::where('direction', 'nakit_havale')->sum('amount'),
            'cash_banka' => $cash('havale')
                + (float) CashTransfer::where('direction', 'nakit_havale')->sum('amount')
                - (float) CashTransfer::where('direction', 'havale_nakit')->sum('amount'),
        ];
    }
}
