<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringInvoiceRequest;
use App\Models\Contact;
use App\Models\Product;
use App\Models\RecurringInvoice;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function index()
    {
        return view('recurring.index', [
            'rules' => RecurringInvoice::with('contact')->orderBy('title')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('recurring.create', [
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

    public function store(StoreRecurringInvoiceRequest $request)
    {
        $validated = $request->validated();

        // Kalemleri kural şablonu olarak sakla (ürün adı kesim anında çözülür)
        $items = collect($validated['items'])
            ->filter(fn ($item) => $item['product_id'] && (float) $item['quantity'] > 0)
            ->map(fn ($item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
            ])
            ->values()
            ->all();

        RecurringInvoice::create([
            'company_id' => $request->user()->company_id,
            'contact_id' => $validated['contact_id'],
            'title' => $validated['title'],
            'day_of_month' => $validated['day_of_month'],
            'tax_rate' => $validated['tax_rate'],
            'notes' => $validated['notes'] ?? null,
            'items' => $items,
        ]);

        $message = "\"{$validated['title']}\" kuralı oluşturuldu — her ayın {$validated['day_of_month']}. gününde kesilecek.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('tekrarlayan.index')
            ->with('success', $message);
    }

    public function destroy(Request $request, RecurringInvoice $rule)
    {
        if (! $request->user()->isAdmin()) {
            return redirect()->route('tekrarlayan.index')
                ->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        $rule->delete();

        return redirect()->route('tekrarlayan.index')
            ->with('success', 'Tekrarlayan fatura kuralı silindi.');
    }
}
