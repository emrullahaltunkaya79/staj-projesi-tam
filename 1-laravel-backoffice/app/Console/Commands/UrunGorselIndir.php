<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Üretilmiş kart görselli ürünlerin ana görselini gerçek ürün fotoğrafıyla değiştirir.
 * Kaynak: Wikimedia Commons (CC fotoğraflar, anahtar kelimeyle eşleşir).
 *
 * Çalıştırma: php artisan urun:gorsel-indir
 */
class UrunGorselIndir extends Command
{
    protected $signature = 'urun:gorsel-indir';

    protected $description = 'Üretilmiş görselli ürünler için gerçek ürün fotoğrafı indirir ve ana görsel yapar';

    /** Ürün adı → denenecek fotoğraf anahtar kelimeleri (sırayla) */
    private const ANAHTARLAR = [
        'mouse' => ['computer,mouse', 'computer,device'],
        'gaming mouse' => ['gaming,mouse'],
        'monitör' => ['computer,monitor', 'computer,screen'],
        'web kamera' => ['webcam', 'camera,lens'],
        'usb-c hub' => ['usb,hub', 'usb,cable'],
        'çanta' => ['laptop,bag', 'bag,leather'],
        'mekanik klavye' => ['mechanical,keyboard', 'computer,keyboard'],
        'klavye' => ['computer,keyboard'],
        'kulaklık standı' => ['headphones'],
        'kulaklık' => ['headphones'],
        'powerbank' => ['battery,charger', 'power,bank'],
        'şarj kablosu' => ['usb,cable'],
        'hoparlör' => ['bluetooth,speaker', 'speaker'],
        'defter' => ['notebook,paper'],
        'kalem' => ['pen,stationery'],
        'zımba' => ['stapler', 'office,supplies'],
        'sandalye' => ['office,chair'],
        'masa lambası' => ['desk,lamp', 'lamp'],
        'organizer' => ['desk,organizer', 'office,desk'],
        'tencere seti' => ['cookware', 'pot,kitchen'],
        'vazo' => ['ceramic,vase', 'vase'],
        'avize' => ['chandelier', 'ceiling,lamp'],
        'yoga matı' => ['yoga,mat'],
        'dambıl seti' => ['dumbbell', 'gym,weights'],
        'matarası' => ['water,bottle'],
        'tıraş makinesi' => ['electric,razor', 'razor'],
        'saç kurutma' => ['hair,dryer'],
        'oyun kolu' => ['gamepad', 'game,controller'],
        'gitar' => ['acoustic,guitar'],
        'mikrofon' => ['microphone'],
        'tripod' => ['tripod,camera'],
        'aksiyon kamera' => ['action,camera', 'gopro'],
        'soundbar' => ['soundbar', 'speaker,home'],
        'projektör' => ['projector'],
        'masaj aleti' => ['massage'],
        'tartı' => ['bathroom,scale', 'scale'],
    ];

    public function handle(): int
    {
        $urunler = Product::whereHas('images', fn ($q) => $q->where('path', 'like', '%uretilen_%'))
            ->with('images')
            ->get();

        $this->info("Üretilmiş görselli {$urunler->count()} ürün bulundu.");

        $basarili = 0;
        $basarisiz = [];

        foreach ($urunler as $urun) {
            $anahtarlar = $this->urunAnahtarlari($urun->name);
            $indirilen = null;

            foreach ($anahtarlar as $anahtar) {
                $indirilen = $this->indir($anahtar, $urun->id);
                if ($indirilen) {
                    break;
                }
            }

            if (! $indirilen) {
                $basarisiz[] = $urun->name;
                continue;
            }

            // Ana görseli gerçek fotoğrafla değiştir; üretilen kartı ikinci görsel olarak koru
            $ana = $urun->images->first(fn ($i) => $i->is_primary) ?? $urun->images->first();
            $kart = $urun->images->first(fn ($i) => $i !== $ana && Str::contains($i->path, 'uretilen_'));

            $ana->update(['path' => $indirilen]);
            if ($kart) {
                $kart->update(['is_primary' => false, 'sort' => 2]);
            }

            $basarili++;
            $this->line("  ✓ {$urun->name} → {$anahtarlar[0]}");
        }

        $this->info("Tamamlandı: {$basarili} ürün güncellendi." . ($basarisiz ? ' Görsel bulunamayan: ' . implode(', ', $basarisiz) : ''));

        return self::SUCCESS;
    }

    /** Ürün adına en uygun anahtar kelime listesi */
    private function urunAnahtarlari(string $ad): array
    {
        $ad = mb_strtolower($ad);
        foreach (self::ANAHTARLAR as $ipucu => $anahtarlar) {
            if (Str::contains($ad, $ipucu)) {
                return array_merge($anahtarlar, ['office,technology']);
            }
        }

        return ['office,technology', 'product'];
    }

    /** Fotoğrafı indirir; geçerli JPEG (≥8 KB) değilse null döner. */
    private function indir(string $anahtar, int $urunId): ?string
    {
        try {
            $arama = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'generator' => 'search',
                    'gsrsearch' => 'filetype:bitmap ' . $anahtar,
                    'gsrnamespace' => 6,
                    'gsrlimit' => 8,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url|mime|size',
                    'iiurlwidth' => 900,
                    'format' => 'json',
                ]);

            $sayfalar = $arama->json('query.pages') ?? [];

            $aday = collect($sayfalar)
                ->map(fn ($p) => $p['imageinfo'][0] ?? null)
                ->filter()
                ->first(fn ($bilgi) => str_starts_with((string) ($bilgi['mime'] ?? ''), 'image/')
                    && (int) ($bilgi['width'] ?? 0) >= 500);

            if (! $aday || empty($aday['thumburl'])) {
                return null;
            }

            $foto = Http::timeout(30)->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])->get($aday['thumburl']);
            $govde = $foto->body();

            if (! $foto->successful() || strlen($govde) < 8192 || substr($govde, 0, 2) !== "\xFF\xD8") {
                return null;
            }

            $dosyaAdi = 'urun_' . $urunId . '_' . Str::random(8) . '.jpg';
            Storage::disk('local')->put('product-images/' . $dosyaAdi, $govde);

            return 'product-images/' . $dosyaAdi;
        } catch (\Throwable $e) {
            $this->line("    ✕ hata ({$anahtar}): " . substr($e->getMessage(), 0, 120));

            return null;
        }
    }
}
