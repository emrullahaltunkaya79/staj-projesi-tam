<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Ürün ana görsellerini web'den (Bing görsel araması üzerinden mağaza ürün
 * fotoğrafları) çeker. Üretilmiş kartlar ve Commons fotoğrafları silinmez —
 * galeriye ikinci görsel olarak kalır.
 *
 * Çalıştırma: php artisan urun:gorsel-web
 */
class UrunGorselWeb extends Command
{
    protected $signature = 'urun:gorsel-web';

    protected $description = 'Web mağaza fotoğraflarından ürün ana görsellerini çeker';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /** Ürün adı → Bing arama terimi */
    private const SORGULAR = [
        'Kablosuz Mouse MX-2200 Sessiz Tıklama' => 'wireless mouse black product photo',
        'Gaming Mouse RGB 8000 DPI 7 Tuş' => 'rgb gaming mouse product',
        '27″ QHD Monitör 165Hz 1ms IPS' => '27 inch qhd gaming monitor product',
        '24″ IPS Full HD Monitör HDMI+VGA' => '24 inch ips monitor product',
        'USB-C Hub 7 Port 4K HDMI + SD Kart' => 'usb c hub 7 port adapter',
        'Web Kamera 1080p Otomatik Odak Mikrofonlu' => '1080p webcam product',
        'Laptop Çantası 15.6″ Su Geçirmez Fermuarlı' => 'laptop bag 15.6 black',
        'Mekanik Klavye TKL RGB Kırmızı Switch' => 'mechanical keyboard tkl rgb',
        'Bluetooth Kulaklık ANC 40 Saat Siyah' => 'anc wireless headphones black product',
        'Powerbank 20.000 mAh 22.5W Hızlı Şarj' => 'powerbank 20000mah product',
        'USB-C Hızlı Şarj Kablosu 2 m örgü' => 'usb c braided charging cable black',
        'Bluetooth Hoparlör 20W Su Geçirmez' => 'jbl flip bluetooth speaker product',
        'Spiralli Defter A4 120 Yaprak Kareli' => 'spiral notebook a4 product',
        'Tükenmez Kalem 10 Lu Mavi 0.7 mm' => 'ballpoint pens blue pack',
        'Zımba Makinesi + 24/6 Tel Seti Metal' => 'metal stapler office',
        'Ergonomik Ofis Sandalyesi Bel Desteği' => 'ergonomic office chair product',
        'Masa Lambası LED 3 Mod Dokunmatik' => 'led desk lamp touch product',
        'Masa Organizer 5 Bölmeli Ahap' => 'wooden desk organizer product',
        'Tencere Seti 7 Parça Granit Kaplama' => 'granite cookware set product',
        'Seramik Vazo 24 cm El Yapımı' => 'ceramic vase decorative',
        'LED Avize Modern 60 cm Üçlü' => 'modern pendant light product',
        'Yoga Matı 6 mm TPE Kaymaz' => 'tpe yoga mat product',
        'Dambıl Seti 2×5 kg Silikon Kaplama' => 'dumbbell set 5kg rubber',
        'Çelik Spor Matarası 750 ml Termos' => 'steel thermos bottle sports',
        'Şarjlı Tıraş Makinesi Su Geçirmez 3 Başlık' => 'electric shaver 3 head product',
        'Saç Kurutma Makinesi 2200W İyonlu' => 'hair dryer 2200w product',
        'Kablosuz Oyun Kolu PC/PS Titreşimli' => 'wireless game controller product',
        'Kulaklık Standı RGB Alüminyum' => 'rgb headphone stand product',
        'Akustik Gitar 41″ Başlangıç Seti Kılıf+Pena' => 'acoustic guitar natural product',
        'USB Mikrofon Podcast Seti Masa Standlı' => 'usb podcast microphone product',
        'Tripod 165 cm Alüminyum Telefon Tutuculu' => 'camera tripod aluminum product',
        'Aksiyon Kamera 4K 30fps Su Geçirmez Kutulu' => '4k action camera product',
        'Soundbar 2.1 Bluetooth 200W Subwooferlı' => 'soundbar 2.1 subwoofer product',
        'Projektör Full HD 1080p 6500 Lümen' => 'full hd projector product',
        'Boynuz Masaj Aleti Shiatsu Şarjlı' => 'electric massager product',
        'Dijital Tartı Cam 180 kg Şarjlı' => 'digital bathroom scale glass product',
    ];

    public function handle(): int
    {
        $basarili = 0;
        $basarisiz = [];

        foreach (self::SORGULAR as $urunAdi => $sorgu) {
            $urun = Product::where('name', $urunAdi)->with('images')->first();
            if (! $urun || $urun->images->isEmpty()) {
                $basarisiz[] = $urunAdi . ' (ürün/görsel yok)';
                continue;
            }

            $indirilen = $this->webdenCek($sorgu, $urun->id);
            if (! $indirilen) {
                $basarisiz[] = $urunAdi;
                continue;
            }

            // Eski ana görseli ikincile indir, yeni fotoğrafı ana görsel yap
            $eski = $urun->images->where('is_primary', true)->first() ?? $urun->images->first();
            $eski->update(['is_primary' => false, 'sort' => max(2, $urun->images->count())]);
            $urun->images()->create([
                'company_id' => $urun->company_id,
                'path' => $indirilen,
                'is_primary' => true,
                'sort' => 1,
            ]);

            $basarili++;
            $this->line("  ✓ {$urunAdi}");
            usleep(1200000); // Bing hız limiti için kısa nefes
        }

        $this->info("Tamamlandı: {$basarili} ürün." . ($basarisiz ? ' Başarısız: ' . implode(', ', $basarisiz) : ''));

        return self::SUCCESS;
    }

    /**
     * Bing görsel aramasından aday URL'leri çeker, ilk geçerli fotoğrafı indirir.
     * Geçerli = image/jpeg|png|webp, ≥ 15 KB, genişlik ≥ 400 px.
     */
    private function webdenCek(string $sorgu, int $urunId): ?string
    {
        try {
            $aramaHtml = Http::timeout(20)->withHeaders(['User-Agent' => self::UA])
                ->get('https://www.bing.com/images/search', [
                    'q' => $sorgu,
                    'form' => 'HDRSC2',
                    'first' => '1',
                ]);

            if (! $aramaHtml->successful()) {
                return null;
            }

            // murl alanları HTML entity kodludur: murl&quot;:&quot;https://…
            preg_match_all('/murl&quot;:&quot;(.*?)&quot;/', $aramaHtml->body(), $eslesmeler);
            $adaylar = collect($eslesmeler[1] ?? [])
                ->map(fn ($u) => html_entity_decode($u))
                ->filter(fn ($u) => ! preg_match('/scribd|lookaside|youtube|facebook|pinterest|wiki|tiktok|instagram/i', $u))
                ->unique()
                ->take(6)
                ->values();

            foreach ($adaylar as $url) {
                try {
                    $foto = Http::timeout(25)->withHeaders([
                        'User-Agent' => self::UA,
                        'Referer' => 'https://www.bing.com/',
                    ])->get($url);

                    if (! $foto->successful() || strlen($foto->body()) < 15360) {
                        continue;
                    }

                    $bilgi = @getimagesizefromstring($foto->body());
                    if (! $bilgi || ($bilgi[0] ?? 0) < 400 || ($bilgi[1] ?? 0) < 400) {
                        continue;
                    }

                    $uzanti = match ($bilgi['mime']) {
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'image/gif' => null,
                        default => 'jpg',
                    };
                    if ($uzanti === null) {
                        continue;
                    }

                    $dosyaAdi = 'web_' . $urunId . '_' . Str::random(8) . '.' . $uzanti;
                    Storage::disk('local')->put('product-images/' . $dosyaAdi, $foto->body());

                    return 'product-images/' . $dosyaAdi;
                } catch (\Throwable) {
                    continue;
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->line("    ✕ ({$sorgu}): " . substr($e->getMessage(), 0, 90));

            return null;
        }
    }
}
