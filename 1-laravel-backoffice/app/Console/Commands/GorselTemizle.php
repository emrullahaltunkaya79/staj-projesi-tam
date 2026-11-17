<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Yetim ürün görsellerini temizler.
 *
 * product_images tablosunda kaydı bulunmayan dosyalar (kalıcı silinmiş ürünlerin
 * görselleri, iptal edilmiş yüklemeler) diskte birikir. Bu komut:
 *  - storage/app/private/product-images içinde referanssız dosyaları bulur,
 *  - son 24 saatte yazılmış dosyalara DOKUNMAZ (sürmekte olan yükleme yarışı),
 *  - --calistir ile siler; aksi halde yalnız raporlar.
 */
class GorselTemizle extends Command
{
    protected $signature = 'gorsel:temizle
        {--calistir : Yetim dosyaları gerçekten sil (verilmezse yalnız raporlanır)}
        {--yasan=24 : Bu saatten yeni dosyalara dokunulmaz}';

    protected $description = 'Referanssız (yetim) ürün görsellerini diskten temizler';

    public function handle(): int
    {
        $dizin = 'product-images';
        $kayitliYollar = DB::table('product_images')
            ->pluck('path')
            ->map(fn ($p) => basename($p))
            ->flip();

        $esikZaman = now()->subHours((int) $this->option('yasan'))->getTimestamp();
        $taranan = 0;
        $yetim = [];
        $korunan = 0;

        foreach (Storage::files($dizin) as $dosya) {
            $taranan++;
            $ad = basename($dosya);

            if ($kayitliYollar->has($ad)) {
                continue; // tabloda kaydı var — canlı görsel
            }

            $zaman = Storage::lastModified($dosya);
            if ($zaman > $esikZaman) {
                $korunan++;
                continue; // yeni dosya — sürmekte olan yükleme olabilir
            }

            $yetim[] = $dosya;
        }

        $this->info("Taranan: {$taranan} · Yetim: " . count($yetim) . " · Korunan (yeni): {$korunan}");

        if ($yetim === []) {
            $this->info('Temizlenecek dosya yok.');

            return self::SUCCESS;
        }

        if (! $this->option('calistir')) {
            $this->warn('Kuru çalışma — silmek için: php artisan gorsel:temizle --calistir');
            foreach (array_slice($yetim, 0, 10) as $dosya) {
                $this->line('  ' . $dosya);
            }
            if (count($yetim) > 10) {
                $this->line('  … ve ' . (count($yetim) - 10) . ' dosya daha');
            }

            return self::SUCCESS;
        }

        Storage::delete($yetim);
        $this->info(count($yetim) . ' yetim görsel silindi.');

        return self::SUCCESS;
    }
}
