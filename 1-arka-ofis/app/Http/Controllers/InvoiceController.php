<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesExport;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    public function index(Request $request)
    {
        $query = Invoice::with('contact')->latest('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        return view('invoices.index', [
            'invoices' => $query->paginate(9)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Fatura listesini Excel (.xlsx) olarak indirir.
     */
    public function export()
    {
        return (new InvoicesExport)->download('faturalar-'.now()->format('Ymd-His').'.xlsx');
    }

    public function create()
    {
        return view('invoices.create', [
            'contacts' => Contact::where('type', 'musteri')->orderBy('name')->get(),
            'warehouses' => \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(),
            // JS tarafına taşınan sadeleştirilmiş ürün verisi (isim, fiyat, stok)
            'products' => $this->productsForForm(),
        ]);
    }

    public function createPurchase()
    {
        return view('invoices.create_purchase', [
            'contacts' => Contact::where('type', 'tedarikci')->orderBy('name')->get(),
            'warehouses' => \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => $this->productsForForm(),
        ]);
    }

    private function productsForForm()
    {
        return Product::orderBy('name')->get()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name.($p->code ? " ($p->code)" : ''),
            'sale_price' => (float) $p->sale_price,
            'purchase_price' => (float) $p->purchase_price,
            'stock_quantity' => $p->stock_quantity,
            'unit' => $p->unit,
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->createSaleInvoice(
            $request->user()->company_id,
            $request->validated()
        );

        $message = "{$invoice->invoice_no} numaralı satış faturası kesildi — stoklar düşürüldü ve cari bakiyesi {$invoice->total} ₺ artırıldı.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('faturalar.show', $invoice)
            ->with('success', $message);
    }

    public function storePurchase(StoreInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->createPurchaseInvoice(
            $request->user()->company_id,
            $request->validated()
        );

        $message = "{$invoice->invoice_no} numaralı alış faturası kaydedildi — stoklar artırıldı ve tedarikçi bakiyesi {$invoice->total} ₺ borçlandı.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('faturalar.show', $invoice)
            ->with('success', $message);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items.product', 'contact', 'company', 'refunds', 'refundOf');

        return view('invoices.show', compact('invoice'));
    }

    /**
     * İade formu: kalemler bazında iade edilecek miktarların girildiği ekran.
     */
    public function refundForm(Invoice $invoice)
    {
        if ($invoice->type !== 'satis') {
            return redirect()->route('faturalar.show', $invoice)
                ->with('error', 'Yalnızca satış faturaları iade edilebilir.');
        }

        $invoice->load('items.product', 'contact', 'refunds.items');

        return view('invoices.refund', compact('invoice'));
    }

    /**
     * Satış faturasını kısmen/tamamen iade eder (stok geri + bakiye düşür).
     */
    public function refund(Request $request, Invoice $invoice)
    {
        $refund = $this->invoiceService->refundSaleInvoice(
            $invoice,
            $request->user()->company_id,
            (array) $request->input('quantities', [])
        );

        return redirect()->route('faturalar.show', $refund)
            ->with('success', "{$refund->invoice_no} numaralı iade faturası kesildi — stoklar geri yüklendi ve cari bakiyesi {$refund->total} ₺ düşürüldü.");
    }

    /**
     * Faturayı PDF olarak indirir.
     * (auth middleware + BelongsToCompany global scope sayesinde
     * yalnızca faturanın sahibi şirket indirebilir.)
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load('items.product', 'contact', 'company');

        return $this->buildPdf($invoice)->download('fatura-'.$invoice->id.'.pdf');
    }

    /**
     * Faturayı PDF ekiyle carinin e-posta adresine gönderir.
     */
    public function email(Request $request, Invoice $invoice)
    {
        $invoice->load('contact', 'company');

        if (! $invoice->contact->email) {
            return redirect()->route('faturalar.show', $invoice)
                ->with('error', 'Cari kaydında e-posta adresi yok — önce cari kartına e-posta ekleyin.');
        }

        $pdf = $this->buildPdf($invoice);

        \Illuminate\Support\Facades\Mail::to($invoice->contact->email)->send(
            new \App\Mail\InvoiceMail($invoice, $pdf->output(), 'fatura-'.$invoice->id.'.pdf')
        );

        return redirect()->route('faturalar.show', $invoice)
            ->with('success', "{$invoice->invoice_no} faturası {$invoice->contact->email} adresine e-posta ile gönderildi.");
    }

    private function buildPdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        // DomPDF font önbellek dizini yoksa oluştur (yoksa "Path must not be empty" hatası verir)
        $fontDir = config('dompdf.options.font_dir', storage_path('fonts'));
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0777, true);
        }

        // mergeWithDefaults=true → config'deki font_dir/font_cache/temp_dir ayarları korunur
        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'default_font' => 'DejaVu Sans',
                'is_html5_parser_enabled' => true,
                'is_remote_enabled' => false,
            ], true);
    }
}
