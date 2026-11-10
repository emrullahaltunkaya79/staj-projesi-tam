<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * Ürün oluştur (satıcı paneli).
     * POST /api/v1/products
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'code' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:64'],
        ], [
            'name.required' => 'Ürün adı zorunludur.',
            'unit.required' => 'Birim zorunludur.',
            'sale_price.required' => 'Satış fiyatı zorunludur.',
            'stock_quantity.required' => 'Stok miktarı zorunludur.',
        ]);

        $validated['company_id'] = $request->user()->company_id;

        $product = Product::create($validated);
        $product->refresh();

        app(WebhookService::class)->productUpsert(
            $product->id, $product->name, $product->code,
            (float) $product->sale_price, (int) $product->stock_quantity,
            $product->category?->name, $product->primaryImageUri(),
        );

        return response()->json([
            'message' => 'Ürün oluşturuldu.',
            'data' => ['id' => $product->id, 'name' => $product->name],
        ], 201);
    }

    /**
     * Ürün güncelle.
     * PUT /api/v1/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)->find($id);

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'purchase_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'discount_percent' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:90'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $product->update($validated);
        $product->refresh();

        app(WebhookService::class)->productUpsert(
            $product->id, $product->name, $product->code,
            (float) $product->sale_price, (int) $product->stock_quantity,
            $product->category?->name, $product->primaryImageUri(),
        );

        return response()->json(['message' => 'Ürün güncellendi.']);
    }

    /**
     * Ürün sil (soft delete).
     * DELETE /api/v1/products/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)->find($id);

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $product->delete();

        app(WebhookService::class)->productDeleted($product->id, $product->name);

        return response()->json(['message' => 'Ürün silindi.']);
    }

    /**
     * Ürün yorumları.
     * GET /api/v1/products/{id}/reviews
     */
    /**
     * Satıcı paneli: tüm yorumlar (onaylı + gizli) — moderasyon listesi.
     * GET /api/v1/yorumlar
     */
    public function yorumModerasyon(): JsonResponse
    {
        $yorumlar = \App\Models\ProductReview::with('product:id,name')
            ->latest()
            ->take(100)
            ->get([
                'id', 'product_id', 'user_name', 'rating', 'comment',
                'image_path', 'is_approved', 'created_at',
            ]);

        return response()->json([
            'data' => $yorumlar->map(fn ($y) => [
                'id' => $y->id,
                'urun' => $y->product?->name ?? '—',
                'kullanici' => $y->user_name,
                'rating' => $y->rating,
                'comment' => $y->comment,
                'gorsel' => $y->image_path,
                'onayli' => (bool) $y->is_approved,
                'tarih' => $y->created_at,
            ]),
        ]);
    }

    /**
     * Moderasyon: yorumu göster/gizle.
     * PATCH /api/v1/yorumlar/{id}/onay
     */
    public function yorumOnay(Request $request, int $id): JsonResponse
    {
        $yorum = \App\Models\ProductReview::find($id);
        if (! $yorum) {
            return response()->json(['message' => 'Yorum bulunamadı.'], 404);
        }

        $yorum->update(['is_approved' => ! $yorum->is_approved]);

        return response()->json([
            'message' => $yorum->is_approved ? 'Yorum gösteriliyor.' : 'Yorum gizlendi.',
            'data' => ['id' => $yorum->id, 'onayli' => (bool) $yorum->is_approved],
        ]);
    }

    /**
     * Moderasyon: yorumu kalıcı sil.
     * DELETE /api/v1/yorumlar/{id}
     */
    public function yorumSil(int $id): JsonResponse
    {
        $silinen = \App\Models\ProductReview::destroy($id);

        return response()->json([
            'message' => $silinen ? 'Yorum silindi.' : 'Yorum bulunamadı.',
        ], $silinen ? 200 : 404);
    }

    public function reviews(int $id): JsonResponse
    {
        $reviews = ProductReview::where('product_id', $id)
            ->where('is_approved', true)
            ->withCount('votes')
            ->latest()
            ->take(20)
            ->get(['id', 'user_name', 'rating', 'comment', 'image_path', 'created_at'])
            ->map(fn ($r) => array_merge($r->toArray(), ['faydali' => (int) $r->votes_count]));

        $ortalamа = ProductReview::where('product_id', $id)
            ->where('is_approved', true)
            ->avg('rating');

        return response()->json([
            'data' => [
                'ortalam_puan' => round((float) $ortalamа, 1),
                'yorum_sayisi' => ProductReview::where('product_id', $id)->where('is_approved', true)->count(),
                'yorumlar' => $reviews,
            ],
        ]);
    }

    /**
     * Yorum "faydalı oldu" oyu — müşteri token'ıyla, oy tekrar tıklanınca geri alınır.
     * POST /api/v1/reviews/{review}/faydali
     */
    public function faydaliToggle(Request $request, int $reviewId): JsonResponse
    {
        $review = ProductReview::find($reviewId);
        abort_if(! $review, 404, 'Yorum bulunamadı.');

        $mevcut = \App\Models\ReviewVote::where('review_id', $reviewId)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($mevcut) {
            $mevcut->delete();
            $oyVerildi = false;
        } else {
            \App\Models\ReviewVote::create(['review_id' => $reviewId, 'user_id' => $request->user()->id]);
            $oyVerildi = true;
        }

        return response()->json([
            'message' => $oyVerildi ? 'Oyunuz kaydedildi.' : 'Oyunuz kaldırıldı.',
            'data' => ['oy_verildi' => $oyVerildi, 'faydali' => \App\Models\ReviewVote::where('review_id', $reviewId)->count()],
        ]);
    }

    /**
     * Yorum ekle (giriş yapmış müşteri).
     * POST /api/v1/products/{id}/reviews
     */
    /**
     * Ürüne görsel yükle (satıcı paneli).
     * POST /api/v1/products/{id}/images
     */
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)->find($id);

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'images.required' => 'En az bir görsel seçilmelidir.',
            'images.*.image' => 'Yüklenen dosya görsel olmalıdır (JPG/PNG/WebP, maks 4 MB).',
        ]);

        $first = $product->images()->count() === 0;

        foreach ($request->file('images') as $i => $file) {
            $product->images()->create([
                'path' => $file->store('product-images'),
                'is_primary' => $first && $i === 0,
                'sort' => $product->images()->count() + $i,
            ]);
        }

        $product->refresh();

        app(WebhookService::class)->productUpsert(
            $product->id, $product->name, $product->code,
            (float) $product->sale_price, (int) $product->stock_quantity,
            $product->category?->name, $product->primaryImageUri(),
        );

        return response()->json([
            'message' => count($request->file('images')).' görsel yüklendi.',
            'data' => ['image_url' => $product->primaryImageUri()],
        ], 201);
    }

    public function addReview(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı.'], 404);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'rating.required' => 'Puan zorunludur (1-5).',
        ]);

        $gorselYolu = null;
        if ($request->hasFile('image')) {
            // multipart: JSON kabulüne ek olarak fotoğraf kabul edilir
            $gorselYolu = 'reviews/'.$request->file('image')->hashName();
            $request->file('image')->storeAs('reviews', basename($gorselYolu), 'public');
        }

        $review = ProductReview::create([
            'product_id' => $id,
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'image_path' => $gorselYolu,
            'is_approved' => true,
        ]);

        return response()->json([
            'message' => 'Değerlendirmeniz kaydedildi.',
            'data' => $review,
        ], 201);
    }
}
