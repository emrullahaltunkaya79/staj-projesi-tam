<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\StockCountExport;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images'])->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->get('low_stock')) {
            $query->where('stock_quantity', '<', 5);
        }

        return view('products.index', [
            'products' => $query->paginate(9)->withQueryString(),
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'filters' => ['search' => $search, 'low_stock' => $request->boolean('low_stock'), 'category' => $categoryId],
        ]);
    }

    /**
     * Ürüne görsel yükler (çoklu).
     */
    public function show(Product $product)
    {
        $product->load(['category', 'images', 'warehouses']);

        return view('products.show', compact('product'));
    }

    public function uploadImage(Request $request, Product $product)
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'images.required' => 'En az bir görsel seçilmelidir.',
            'images.*.image' => 'Yüklenen dosya görsel olmalıdır (JPG/PNG/WebP, en fazla 4 MB).',
            'images.*.max' => 'Görsel en fazla 4 MB olabilir.',
        ]);

        $first = $product->images()->count() === 0; // ilk görsel otomatik ana görsel

        foreach ($request->file('images') as $i => $file) {
            $product->images()->create([
                'path' => $file->store('product-images'),
                'is_primary' => $first && $i === 0,
                'sort' => $product->images()->count() + $i,
            ]);
        }

        return back()->with('success', count($request->file('images')).' görsel yüklendi.');
    }

    /**
     * Görseli siler.
     */
    public function destroyImage(Request $request, Product $product, ProductImage $image)
    {
        $image = \App\Models\ProductImage::where('product_id', $product->id)->where('id', $image->id)->firstOrFail();

        Storage::delete($image->path);
        $image->delete();

        return back()->with('success', 'Görsel silindi.');
    }

    public function create()
    {
        return view('products.create', ['product' => new Product(['unit' => 'adet', 'stock_quantity' => 0])]);
    }

    /**
     * Ürün/stok listesini Excel (.xlsx) olarak indirir.
     */
    public function export()
    {
        return (new ProductsExport)->download('urunler-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Boş "Sayılan" sütunlu stok sayım listesi indirir.
     */
    public function exportStockCount()
    {
        return (new StockCountExport)->download('stok-sayim-'.now()->format('Ymd-His').'.xlsx');
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());
        $this->notifyProductUpsert($product);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Ürün oluşturuldu.']);
        }

        return redirect()->route('urunler.index')
            ->with('success', 'Ürün oluşturuldu.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        $this->notifyProductUpsert($product);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "«{$product->name}» güncellendi."]);
        }

        return redirect()->route('urunler.index')
            ->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Request $request, Product $product)
    {
        if (! $request->user()->can('delete', $product)) {
            return redirect()->route('urunler.index')
                ->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        $product->delete();
        app(\App\Services\WebhookService::class)->productDeleted($product->id, $product->name);

        return redirect()->route('urunler.index')
            ->with('success', 'Ürün silindi.');
    }

    /**
     * Katalog senkronizasyonu: ürün değişikliğini e-ticaret tarafına bildir.
     */
    private function notifyProductUpsert(Product $product): void
    {
        $product->refresh();

        app(\App\Services\WebhookService::class)->productUpsert(
            $product->id,
            $product->name,
            $product->code,
            (float) $product->sale_price,
            (int) $product->stock_quantity,
            $product->category?->name,
            $product->primaryImageUri(),
        );
    }
}
