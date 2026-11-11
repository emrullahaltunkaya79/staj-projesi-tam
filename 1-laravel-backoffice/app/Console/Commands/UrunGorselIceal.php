<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Kullanıcının indirdiği fotoğrafları ürünlere ana görsel olarak içe aktarır.
 *
 * Kullanım:
 *   1. Fotoğrafları storage/app/gorsel-iceal klasörüne koy:
 *        168.jpg                     → ürün ID'si ile (168.png de olur)
 *        dambil-seti.jpg             → ya da ürün adının noktasız küçük haliyle
 *        "27 qhd monitor.jpg" gibi
 *   2. php artisan urun:gorsel-iceal
 *
 * Yüklenen dosya ana görsel olur; eski ana görsel galeride ikincil kalır.
 * İşlenen dosyalar gorsel-iceal/yuklendi altına taşınır.
 */
class UrunGorselIceal extends Command
{
    protected $signature = 'urun:gorsel-iceal';

    protected $description = 'storage/app/gorsel-iceal içindeki fotoğrafları ürünlere ana görsel olarak yükler';

    public function handle(): int
    {
        $dosyalar = Storage::disk('local')->files('gorsel-iceal');

        if (empty($dosyalar)) {
            $this->info('gorsel-iceal klasörü boş.');
            $this->line('Fotoğrafları storage/app/gorsel-iceal/ içine şöyle koyun:');
            $this->line('  168.jpg  (ürün ID)  veya  dambil-seti.jpg  (ürün adının küçük harf hali)');
            $this->line('Sonra tekrar çalıştırın: php artisan urun:gorsel-iceal');

            return self::SUCCESS;
        }

        $basarili = 0;
        $hatali = 0;

        foreach ($dosyalar as $dosya) {
            $dosyaAdi = pathinfo($dosya, PATHINFO_FILENAME);
            $uzanti = strtolower(pathinfo($dosya, PATHINFO_EXTENSION));

            if (! in_array($uzanti, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }

            // Dosya adı → ürün: "168" → ID; değilse ürün adının küçük harf haliyle eşle
            $urun = ctype_digit($dosyaAdi)
                ? Product::find((int) $dosyaAdi)
                : Product::whereRaw('LOWER(name) = ?', [str_replace('-', ' ', $dosyaAdi)])->first();

            if (! $urun) {
                $this->warn("  ? {$dosyaAdi}.{$uzanti} → eşleşen ürün bulunamadı");
                continue;
            }

            $govde = Storage::disk('local')->get($dosya);
            $bilgi = @getimagesizefromstring((string) $govde);

            if (! $bilgi || strlen((string) $govde) < 10240) {
                $this->warn("  ✕ {$dosyaAdi}.{$uzanti} → geçerli görsel değil (≥10 KB olmalı)");
                $hatali++;
                continue;
            }

            $uzanti = match ($bilgi['mime']) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $yeniYol = 'product-images/iceal_' . $urun->id . '_' . now()->format('His') . '.' . $uzanti;
            Storage::disk('local')->put($yeniYol, $govde);

            // Eski ana görseli ikincile indir, yüklenen fotoğrafı ana görsel yap
            $urun->images()->update(['is_primary' => false]);
            $urun->images()->create([
                'company_id' => $urun->company_id,
                'path' => $yeniYol,
                'is_primary' => true,
                'sort' => 1,
            ]);

            // İşlenen dosyayı arşivle (tekrar işlenmesin)
            Storage::disk('local')->move($dosya, 'gorsel-iceal/yuklendi/' . pathinfo($dosya, PATHINFO_BASENAME));

            $basarili++;
            $this->line("  ✓ {$urun->id}: {$urun->name} → {$yeniYol}");
        }

        $this->info("Tamamlandı: {$basarili} ürün güncellendi." . ($hatali ? " {$hatali} geçersiz dosya atlandı." : ''));

        return self::SUCCESS;
    }
}
