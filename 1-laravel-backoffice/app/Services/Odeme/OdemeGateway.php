<?php

namespace App\Services\Odeme;

use App\Models\Order;

/**
 * Kart ödeme sağlayıcı sözleşmesi — 3D Secure akışı.
 * baslat: PSP tarafında ödemeyi açar, yönlendirme adresi + token döner.
 * dogrula: PSP geri dönüşünde ödemenin onaylandığını doğrular.
 */
interface OdemeGateway
{
    /** @return array{odeme_url: string, token: string} */
    public function baslat(Order $siparis): array;

    /** PSP sonucunu doğrular (mock: OTP; iyzico: auth detail sorgusu). */
    public function dogrula(Order $siparis, array $girdi = []): bool;

    public function ad(): string;
}
