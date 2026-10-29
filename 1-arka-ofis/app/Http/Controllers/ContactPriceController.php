<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactPrice;
use App\Models\Product;
use Illuminate\Http\Request;

class ContactPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactPrice::with(['contact', 'product'])->orderBy('id');

        if ($contactId = $request->get('contact_id')) {
            $query->where('contact_id', $contactId);
        }

        return view('prices.index', [
            'prices' => $query->paginate(9)->withQueryString(),
            'contacts' => Contact::where('type', 'musteri')->orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'filters' => ['contact_id' => $contactId],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'price' => ['required', 'numeric', 'min:0.01'],
        ], [
            'contact_id.required' => 'Cari seçilmelidir.',
            'product_id.required' => 'Ürün seçilmelidir.',
            'price.required' => 'Fiyat zorunludur.',
            'price.numeric' => 'Fiyat sayı olmalıdır.',
            'price.min' => 'Fiyat sıfırdan büyük olmalıdır.',
        ]);

        // Varolan kayıt varsa güncelle (upsert)
        ContactPrice::updateOrCreate(
            ['contact_id' => $validated['contact_id'], 'product_id' => $validated['product_id']],
            ['price' => $validated['price'], 'company_id' => $request->user()->company_id]
        );

        $contact = Contact::find($validated['contact_id']);
        $product = Product::find($validated['product_id']);

        return redirect()->route('fiyatlar.index')
            ->with('success', "{$contact->name} — {$product->name} özel fiyatı kaydedildi.");
    }

    public function destroy(Request $request, ContactPrice $price)
    {
        if (! $request->user()->isAdmin()) {
            return redirect()->route('fiyatlar.index')
                ->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        $price->delete();

        return redirect()->route('fiyatlar.index')
            ->with('success', 'Özel fiyat kaydı silindi.');
    }
}
