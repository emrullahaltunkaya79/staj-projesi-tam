<?php

namespace App\Services;

use App\Mail\KritikStokMail;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Kritik stok denetimi ve bildirimi.
 *
 * Ürün stoğu min_stock_level altına düştüğünde yapılandırılmış adrese
 * (varsayılan emrullahaltunkaya79@gmail.com) tek seferlik uyarı gönderir.
 * low_stock_notified_at damgası, stok yenilenene kadar tekrar gönderimi engeller;
 * stok eşik üstüne toparlandığında damga sıfırlanır ve ürün yeniden uyarıya açılır.
 */
class KritikStokServisi
{
    /** Bildirim alıcısı — config/mail.php: kritik_stok_alici (.env: KRITIK_STOK_MAIL_ALICI) */
    public static function alici(): string
    {
        return (string) config('mail.kritik_stok_alici', 'emrullahaltunkaya79@gmail.com');
    }

    /**
     * Ürün stoğunu kritik eşite göre denetler.
     * - Eşik altına düştüğünde (ve daha önce bildirilmemişse) e-posta gönderilir.
     * - Eşik üstüne toparlandığında bildirim damgası sıfırlanır.
     */
    public function denetle(Product $urun): void
    {
        if (! $urun->exists) {
            return;
        }

        if ($urun->kritikStokta()) {
            if ($urun->low_stock_notified_at !== null) {
                return; // zaten bildirildi — stok yenilenene kadar spam yok
            }

            try {
                Mail::to(self::alici())->send(new KritikStokMail($urun));
                $urun->forceFill(['low_stock_notified_at' => now()])->saveQuietly();

                Log::info("Kritik stok bildirimi gönderildi: {$urun->name} ({$urun->stock_quantity} adet)");
            } catch (\Throwable $e) {
                // Mail gönderimi başarısız olsa bile stok akışı bozulmaz; damga yazılmaz,
                // bir sonraki stok hareketinde yeniden denemek için sessizce geçilir
                Log::warning('Kritik stok maili gönderilemedi: '.$e->getMessage());
            }

            return;
        }

        // Toparlanma: stok eşik üstüne çıktı — bildirim damgasını sıfırla
        if ($urun->low_stock_notified_at !== null) {
            $urun->forceFill(['low_stock_notified_at' => null])->saveQuietly();
            Log::info("Stok toparlandı, kritik bildirim damgası sıfırlandı: {$urun->name}");
        }
    }
}
