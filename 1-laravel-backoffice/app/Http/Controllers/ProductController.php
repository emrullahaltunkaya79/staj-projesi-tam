<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\StockCountExport;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // Ürün bazlı kritik eşik: stoğu kendi eşiğinin altında/eşit olanlar
            $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
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

        $yuklenen = 0;
        $yazilanYollar = [];

        try {
            DB::transaction(function () use ($request, $product, &$yuklenen, &$yazilanYollar) {
                $first = $product->images()->count() === 0; // ilk görsel otomatik ana görsel
                $taban = $product->images()->count();

                foreach ($request->file('images') as $i => $file) {
                    $yol = $file->store('product-images');
                    $mutlak = Storage::disk('local')->path($yol);
                    $this->optimizeImage($mutlak);

                    if (! is_file($mutlak)) {
                        throw new \RuntimeException('Görsel dosyası diske yazılamadı. Depolama alanını ve yazma izinlerini kontrol edin.');
                    }

                    $product->images()->create([
                        'path' => $yol,
                        'is_primary' => $first && $i === 0,
                        'sort' => $taban + $i,
                    ]);

                    $yazilanYollar[] = $yol;
                    $yuklenen++;
                }
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Yarı kalmış dosyaları temizle — bozuk görsel kaydı kalmasın
            foreach ($yazilanYollar as $yol) {
                Storage::delete($yol);
            }

            report($e);

            return back()->with('error', 'Görsel yüklenirken bir sorun oluştu: '.$e->getMessage().' Kayıt tamamlanamadı, lütfen tekrar deneyin.');
        }

        // Mağazaya bildir: görsel değişimi katalog + görsel önbelleklerini tazeler
        $product->refresh();
        app(\App\Services\WebhookService::class)->productUpsert(
            $product->id,
            $product->name,
            $product->code,
            (float) $product->sale_price,
            (int) $product->stock_quantity,
            $product->category?->name,
        );

        return back()->with('success', $yuklenen.' görsel yüklendi ve mağazaya yansıtıldı.');
    }

    /**
     * Görseli siler.
     */
    public function destroyImage(Request $request, Product $product, ProductImage $image)
    {
        $image = \App\Models\ProductImage::where('product_id', $product->id)->where('id', $image->id)->firstOrFail();

        $anaSilindi = $image->is_primary;
        try {
            Storage::delete($image->path);
        } catch (\Throwable $e) {
            // Dosya diskte yoksa bile kaydı silmeye devam et — ölü dosya kaydı kalmasın
            report($e);
        }
        $image->delete();

        // Ana görsel silindiyse sıradaki görsel ana görsel olur — mağaza görselsiz kalmasın
        if ($anaSilindi && $product->images()->exists()) {
            $product->images()
                ->orderByDesc('is_primary')->orderBy('sort')->orderBy('id')
                ->first()?->update(['is_primary' => true]);
        }

        // Mağazaya bildir: görsel listesi değişti
        $product->refresh();
        app(\App\Services\WebhookService::class)->productUpsert(
            $product->id,
            $product->name,
            $product->code,
            (float) $product->sale_price,
            (int) $product->stock_quantity,
            $product->category?->name,
        );

        return back()->with('success', 'Görsel silindi ve mağazaya yansıtıldı.');
    }

    /**
     * Panel: görseli ürünün ana görseli yapar.
     */
    public function setPrimaryImage(Request $request, Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        \App\Models\ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        // Mağazaya bildir: ana görsel değişti (mağaza ilk görseli ana olarak gösterir)
        $product->refresh();
        app(\App\Services\WebhookService::class)->productUpsert(
            $product->id,
            $product->name,
            $product->code,
            (float) $product->sale_price,
            (int) $product->stock_quantity,
            $product->category?->name,
        );

        return back()->with('success', 'Ana görsel güncellendi ve mağazaya yansıtıldı.');
    }

    /**
     * Panel: görseli kimlik doğrulamalı olarak servis eder (/api/img mağaza ucudur, panelde kullanılmaz).
     */
    public function showImage(Request $request, Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        $mutlak = Storage::disk('local')->path($image->path);
        abort_unless(is_file($mutlak), 404);

        $mime = match (strtolower(pathinfo($mutlak, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return response()->file($mutlak, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=300']);
    }

    /**
     * Yüklenen görseli 1600 px sınırına küçültür ve sıkıştırır — mağaza sayfaları hızlı kalsın.
     */
    private function optimizeImage(string $mutlakYol): void
    {
        $bilgi = @getimagesize($mutlakYol);
        if (! $bilgi) {
            return;
        }

        [$genislik, $yukseklik] = $bilgi;
        $maks = 1600;
        if ($genislik <= $maks && $yukseklik <= $maks) {
            return;
        }

        $oran = max($genislik / $maks, $yukseklik / $maks);
        $yeniGen = (int) round($genislik / $oran);
        $yeniYuk = (int) round($yukseklik / $oran);

        $img = match ($bilgi['mime']) {
            'image/png' => @imagecreatefrompng($mutlakYol),
            'image/webp' => @imagecreatefromwebp($mutlakYol),
            default => @imagecreatefromjpeg($mutlakYol),
        };
        if (! $img) {
            return;
        }

        $kucuk = imagescale($img, $yeniGen, $yeniYuk);
        match ($bilgi['mime']) {
            'image/png' => imagepng($kucuk, $mutlakYol, 8),
            'image/webp' => imagewebp($kucuk, $mutlakYol, 85),
            default => imagejpeg($kucuk, $mutlakYol, 85),
        };

        imagedestroy($img);
        imagedestroy($kucuk);
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
        $product->loadMissing('images');

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
