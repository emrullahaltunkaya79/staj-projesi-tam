<?php

namespace App\Services\Odeme;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

/**
 * 3DS simülasyonu — iyzico anahtarları tanımlı değilken kullanılır.
 * Tam akışı birebir yürütür: ödeme başlatma → PSP formu → SMS kodu → onay.
 * Kart bilgisi saklanmaz; yalnızca form davranışını simüle eder.
 */
class MockGateway implements OdemeGateway
{
    public function baslat(Order $siparis): array
    {
        $token = '3DS-' . strtoupper(bin2hex(random_bytes(8)));
        $kod = (string) random_int(100000, 999999);

        // Sandbox'ta SMS kodu formda görünür (gerçek PSP'de telefonunuza gelir)
        Cache::put("3ds-otp:{$token}", $kod, now()->addMinutes(10));
        Cache::put("3ds-deneme:{$token}", 0, now()->addMinutes(10));

        return ['odeme_url' => url('/odeme/3ds/' . $token), 'token' => $token];
    }

    public function dogrula(Order $siparis, array $girdi = []): bool
    {
        $token = (string) ($siparis->odeme_token ?? '');
        if ($token === '') {
            return false;
        }

        $beklenen = (string) Cache::get("3ds-otp:{$token}");
        $deneme = (int) Cache::get("3ds-deneme:{$token}", 0);

        if ($deneme >= 5) {
            return false; // kod deneme hakkı bitti
        }

        if ($beklenen === '' || ! hash_equals($beklenen, (string) ($girdi['otp'] ?? ''))) {
            Cache::put("3ds-deneme:{$token}", $deneme + 1, now()->addMinutes(10));

            return false;
        }

        Cache::forget("3ds-otp:{$token}");
        Cache::forget("3ds-deneme:{$token}");

        return true;
    }

    /** Sandbox formunda gösterilecek tek kullanımlık kod. */
    public function simdikiKod(Order $siparis): ?string
    {
        return Cache::get('3ds-otp:' . ($siparis->odeme_token ?? ''));
    }

    public function ad(): string
    {
        return 'mock';
    }
}
