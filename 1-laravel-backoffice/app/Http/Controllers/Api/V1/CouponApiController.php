<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponApiController extends Controller
{
    /**
     * Kupon doğrula + indirim hesapla.
     * POST /api/v1/coupons/validate
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'cart_total' => ['required', 'numeric', 'min:0'],
        ], [
            'code.required' => 'Kupon kodu giriniz.',
        ]);

        $sonuc = app(CouponService::class)->validate(
            $validated['code'],
            (float) $validated['cart_total'],
        );

        return response()->json([
            'valid' => $sonuc['gecerli'],
            'message' => $sonuc['mesaj'],
            'data' => $sonuc['gecerli'] ? [
                'code' => $sonuc['kupon']->code,
                'name' => $sonuc['kupon']->name,
                'type' => $sonuc['kupon']->type,
                'discount' => $sonuc['indirim'],
                'type_label' => $sonuc['kupon']->typeLabel(),
            ] : null,
        ]);
    }

    /**
     * Aktif kuponları listele (satıcı paneli).
     * GET /api/v1/coupons
     */
    public function index(): JsonResponse
    {
        $kuponlar = \App\Models\Coupon::where('is_active', true)
            ->orderByDesc('id')
            ->get(['id', 'code', 'name', 'type', 'value', 'min_order_total', 'used_count', 'usage_limit', 'expires_at']);

        return response()->json(['data' => $kuponlar]);
    }

    /**
     * Satıcı paneli: yeni kupon oluştur.
     * POST /api/v1/coupons
     */
    public function store(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:percent,fixed,free_shipping'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $kupon = \App\Models\Coupon::create([
            'company_id' => $request->user()->company_id,
            'code' => strtoupper($veri['code']),
            'name' => $veri['name'],
            'type' => $veri['type'],
            'value' => $veri['value'],
            'min_order_total' => $veri['min_order_total'] ?? 0,
            'usage_limit' => $veri['usage_limit'] ?? null,
            'expires_at' => $veri['expires_at'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Kupon oluşturuldu.',
            'data' => ['id' => $kupon->id, 'code' => $kupon->code],
        ], 201);
    }

    /**
     * Satıcı paneli: kupon pasifleştir.
     * DELETE /api/v1/coupons/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $etkilenen = \App\Models\Coupon::where('company_id', $request->user()->company_id)
            ->where('id', $id)
            ->update(['is_active' => false]);

        return response()->json([
            'message' => $etkilenen ? 'Kupon devre dışı bırakıldı.' : 'Kupon bulunamadı.',
        ], $etkilenen ? 200 : 404);
    }
}
