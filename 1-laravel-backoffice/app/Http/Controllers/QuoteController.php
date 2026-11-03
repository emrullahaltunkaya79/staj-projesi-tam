<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Product;
use App\Models\Quote;
use App\Http\Requests\StoreQuoteRequest;
use App\Services\QuoteService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quoteService)
    {
    }

    public function index(Request $request)
    {
        $query = Quote::with('contact')->latest('id');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('quotes.index', [
            'quotes' => $query->paginate(15)->withQueryString(),
            'filters' => ['status' => $status],
        ]);
    }

    public function create()
    {
        return view('quotes.create', [
            'contacts' => Contact::where('type', 'musteri')->orderBy('name')->get(),
            'products' => Product::orderBy('name')->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name.($p->code ? " ($p->code)" : ''),
                'sale_price' => (float) $p->sale_price,
                'stock_quantity' => $p->stock_quantity,
                'unit' => $p->unit,
            ]),
        ]);
    }

    public function store(StoreQuoteRequest $request)
    {
        $quote = $this->quoteService->createQuote($request->user()->company_id, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "{$quote->quote_no} numaralı teklif oluşturuldu."]);
        }

        return redirect()->route('teklifler.show', $quote)
            ->with('success', "{$quote->quote_no} numaralı teklif oluşturuldu.");
    }

    public function show(Quote $quote)
    {
        $quote->load('items.product', 'contact', 'company', 'convertedInvoice');

        return view('quotes.show', compact('quote'));
    }

    /**
     * Teklifi PDF olarak indirir.
     */
    public function downloadPdf(Quote $quote)
    {
        $quote->load('items.product', 'contact', 'company');

        return $this->buildQuotePdf($quote)->download('teklif-'.$quote->id.'.pdf');
    }

    /**
     * Teklifi PDF ekiyle carinin e-posta adresine gönderir.
     */
    public function email(Request $request, Quote $quote)
    {
        $quote->load('contact', 'company');

        if (! $quote->contact->email) {
            return redirect()->route('teklifler.show', $quote)
                ->with('error', 'Cari kaydında e-posta adresi yok — önce cari kartına e-posta ekleyin.');
        }

        \Illuminate\Support\Facades\Mail::to($quote->contact->email)->send(
            new \App\Mail\QuoteMail($quote, $this->buildQuotePdf($quote)->output(), 'teklif-'.$quote->id.'.pdf')
        );

        return redirect()->route('teklifler.show', $quote)
            ->with('success', "{$quote->quote_no} teklifi {$quote->contact->email} adresine e-posta ile gönderildi.");
    }

    private function buildQuotePdf(Quote $quote): \Barryvdh\DomPDF\PDF
    {
        $fontDir = config('dompdf.options.font_dir', storage_path('fonts'));
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0777, true);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('quotes.pdf', ['quote' => $quote])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'default_font' => 'DejaVu Sans',
                'is_html5_parser_enabled' => true,
                'is_remote_enabled' => false,
            ], true);
    }

    public function edit(Quote $quote)
    {
        $quote->load('items');

        // JS tarafına taşınacak hazır doldurma verisi (mevcut kalemler)
        $prefill = $quote->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->all();

        return view('quotes.edit', [
            'quote' => $quote,
            'prefill' => $prefill,
            'contacts' => Contact::where('type', 'musteri')->orderBy('name')->get(),
            'products' => Product::orderBy('name')->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name.($p->code ? " ($p->code)" : ''),
                'sale_price' => (float) $p->sale_price,
                'stock_quantity' => $p->stock_quantity,
                'unit' => $p->unit,
            ]),
        ]);
    }

    public function update(StoreQuoteRequest $request, Quote $quote)
    {
        $this->quoteService->updateQuote($quote, $request->user()->company_id, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "{$quote->fresh()->quote_no} numaralı teklif güncellendi."]);
        }

        return redirect()->route('teklifler.show', $quote)
            ->with('success', "{$quote->fresh()->quote_no} numaralı teklif güncellendi.");
    }

    public function destroy(Request $request, Quote $quote)
    {
        if (! $request->user()->can('delete', $quote)) {
            return redirect()->route('teklifler.index')
                ->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        $quote->delete(); // soft delete — geri dönüşüm kutusundan geri yüklenebilir

        return redirect()->route('teklifler.index')
            ->with('success', "{$quote->quote_no} numaralı teklif silindi (geri dönüşüm kutusunda).");
    }

    public function changeStatus(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:acik,onaylandi,reddedildi'],
        ], ['status.in' => 'Geçersiz durum.']);

        $this->quoteService->changeStatus($quote, $validated['status'], $request->user()->company_id);

        return redirect()->route('teklifler.show', $quote)
            ->with('success', "Teklif durumu güncellendi: {$quote->fresh()->statusLabel()}.");
    }

    public function convert(Request $request, Quote $quote)
    {
        $invoice = $this->quoteService->convertToInvoice($quote, $request->user()->company_id);

        return redirect()->route('faturalar.show', $invoice)
            ->with('success', "{$quote->quote_no} teklifi {$invoice->invoice_no} numaralı satış faturasına dönüştürüldü.");
    }
}
