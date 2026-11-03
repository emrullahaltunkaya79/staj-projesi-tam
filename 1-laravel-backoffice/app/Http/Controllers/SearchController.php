<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactPrice;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Global arama: cariler, ürünler ve faturalarda arar (şirket kapsamı
 * global scope ile otomatik uygulanır). Sonuçlar gruplu JSON döner.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json(['cariler' => [], 'urunler' => [], 'faturalar' => []]);
        }

        $like = "%{$q}%";

        $cariler = Contact::where(fn ($w) => $w->where('name', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->orWhere('email', 'like', $like))
            ->limit(5)
            ->get(['id', 'name', 'type'])
            ->map(fn ($c) => [
                'title' => $c->name,
                'subtitle' => $c->typeLabel(),
                'url' => route('cariler.show', $c->id),
            ]);

        $urunler = Product::where(fn ($w) => $w->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->orWhere('barcode', 'like', $like))
            ->limit(5)
            ->get(['id', 'name', 'code', 'stock_quantity', 'unit'])
            ->map(fn ($p) => [
                'title' => $p->name,
                'subtitle' => ($p->code ? $p->code.' · ' : '')."Stok: {$p->stock_quantity} {$p->unit}",
                'url' => route('urunler.index', ['search' => $p->name]),
            ]);

        $faturalar = Invoice::with('contact')
            ->where(fn ($w) => $w->where('invoice_no', 'like', $like)
                ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', $like)))
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn ($f) => [
                'title' => $f->invoice_no.' — '.$f->contact->name,
                'subtitle' => $f->typeShortLabel().' · '.$f->issue_date->format('d.m.Y'),
                'url' => route('faturalar.show', $f->id),
            ]);

        return response()->json([
            'cariler' => $cariler,
            'urunler' => $urunler,
            'faturalar' => $faturalar,
        ]);
    }

    /**
     * Seçili carinin özel fiyat listesi: {product_id: price} haritası.
     */
    public function prices(Request $request)
    {
        $contactId = (int) $request->query('contact_id');
        if (! $contactId) {
            return response()->json((object) []);
        }

        $prices = ContactPrice::where('contact_id', $contactId)
            ->pluck('price', 'product_id');

        return response()->json($prices);
    }
}
