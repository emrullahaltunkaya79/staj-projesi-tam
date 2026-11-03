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
}
