<?php

namespace App\Services;

use App\Models\Coupon;

class CouponService
{
    /**
     * Kupon doğrular ve indirim tutarını hesaplar.
     *
     * @return array{gecerli: bool, mesaj: string, indirim: float, kupon: Coupon|null}
     */
    public function validate(string $code, float $sepetToplami): array
    {
        $kupon = Coupon::where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->first();

        if (! $kupon) {
            return ['gecerli' => false, 'mesaj' => 'Kupon kodu bulunamadı.', 'indirim' => 0, 'kupon' => null];
        }

        if ($kupon->starts_at && now()->lt($kupon->starts_at)) {
            return ['gecerli' => false, 'mesaj' => 'Bu kupon henüz başlamadı.', 'indirim' => 0, 'kupon' => null];
        }

        if ($kupon->expires_at && now()->gt($kupon->expires_at)) {
            return ['gecerli' => false, 'mesaj' => 'Bu kuponun süresi doldu.', 'indirim' => 0, 'kupon' => null];
        }

        if ($kupon->usage_limit && $kupon->used_count >= $kupon->usage_limit) {
            return ['gecerli' => false, 'mesaj' => 'Bu kuponun kullanım limiti doldu.', 'indirim' => 0, 'kupon' => null];
        }

        if ($sepetToplami < (float) $kupon->min_order_total) {
            $min = number_format((float) $kupon->min_order_total, 2, ',', '.');
            return ['gecerli' => false, 'mesaj' => "Bu kupon en az {$min} ₺ sepet tutarında geçerlidir.", 'indirim' => 0, 'kupon' => null];
        }

        $indirim = match ($kupon->type) {
            'percent' => round($sepetToplami * ((float) $kupon->value / 100), 2),
            'fixed' => min((float) $kupon->value, $sepetToplami),
            'free_shipping' => 0, // kargo indirimi checkout'ta ayrı işlenir
        };

        return [
            'gecerli' => true,
            'mesaj' => "✓ {$kupon->name} uygulandı",
            'indirim' => $indirim,
            'kupon' => $kupon,
        ];
    }

    /**
     * Kupon kullanıldı — sayacı artır.
     */
    public function markUsed(Coupon $kupon): void
    {
        $kupon->increment('used_count');
    }
}
