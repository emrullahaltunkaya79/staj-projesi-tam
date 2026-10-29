<?php

namespace App\Http\Controllers;

use App\Services\Backoffice\ApiClient;
use RuntimeException;

class ShopController extends Controller
{
    /**
     * Vitrin: arka ofis kataloğundan ürün kartları; arama + kategori filtresi.
     */
    public function home()
    {
        $products = [];
        $error = null;

        try {
            $hamKatalog = cache()->remember('shop.catalog', now()->addMinutes(5), fn () => ApiClient::make()->products());
            $kategoriler = collect($hamKatalog)->pluck('category')->filter()->unique()->sort()->values()->all();

            // Kategori filtresi
            $kategori = request('category');
            $products = $hamKatalog;
            if ($kategori !== null && $kategori !== '') {
                $products = array_values(array_filter($products, fn ($p) => ($p['category'] ?? '') === $kategori));
            }

            // Arama: ad / kod / barkod (büyük-küçük harf duyarsız)
            if ($q = trim((string) request('q'))) {
                $products = array_values(array_filter($products, function ($p) use ($q) {
                    return mb_stripos($p['name'] ?? '', $q) !== false
                        || mb_stripos($p['code'] ?? '', $q) !== false
                        || mb_stripos($p['barcode'] ?? '', $q) !== false;
                }));
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            $kategoriler = [];
        }

        return view('shop.home', [
            'products' => $products ?? [],
            'error' => $error,
            'storeName' => config('backoffice.name'),
            'kategoriler' => $kategoriler,
        ]);
    }

    public function product(int $id)
    {
        $product = ApiClient::make()->product($id);

        abort_if(empty($product), 404);

        return view('shop.product', [
            'product' => $product,
            'storeName' => config('backoffice.name'),
        ]);
    }
}
