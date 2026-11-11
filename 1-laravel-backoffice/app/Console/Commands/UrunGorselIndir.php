<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Üretilmiş/kart görselli ürünlerin ana görselini gerçek ürün fotoğrafıyla değiştirir.
 * Kaynak: Wikimedia Commons arama API'si. Seçici iki güvenlik filtresiyle çalışır:
 *  - aday dosyanın BAŞLIĞINDA ürünle ilgili kelime geçmeli (ilgisiz fotoğraf eleme)
 *  - başlıkta yasaklı kelime varsa (amiga, retro, screenshot, diyagram…) eleme
 *
 * Çalıştırma: php artisan urun:gorsel-indir
 */
class UrunGorselIndir extends Command
{
    protected $signature = 'urun:gorsel-indir';

    protected $description = 'Üretilmiş görselli ürünler için gerçek ürün fotoğrafı indirir ve ana görsel yapar';

    /** Ürün ipucu → [Commons arama terimi, başlıkta geçmesi gereken kelimeler (herhangi biri)] */
    private const ESLEME = [
        'mouse' => ['computer mouse', ['mouse']],
        'gaming mouse' => ['gaming mouse', ['mouse']],
        'monitör' => ['computer monitor flat', ['monitor', 'display']],
        'web kamera' => ['webcam', ['webcam', 'web camera']],
        'usb-c hub' => ['usb hub', ['hub', 'usb']],
        'çanta' => ['laptop bag', ['bag', 'case', 'sleeve']],
        'mekanik klavye' => ['mechanical keyboard', ['keyboard']],
        'klavye' => ['computer keyboard', ['keyboard']],
        'kulaklık standı' => ['headphone stand', ['headphone', 'stand']],
        'kulaklık' => ['headphones', ['headphone', 'headset']],
        'powerbank' => ['power bank charger', ['power', 'bank', 'battery', 'charger']],
        'şarj kablosu' => ['usb cable', ['cable', 'usb']],
        'hoparlör' => ['bluetooth speaker', ['speaker']],
        'defter' => ['spiral notebook', ['notebook', 'notepad']],
        'kalem' => ['ballpoint pen', ['pen', 'ballpoint']],
        'zımba' => ['stapler', ['stapler']],
        'sandalye' => ['office chair', ['chair']],
        'masa lambası' => ['desk lamp', ['lamp', 'light']],
        'organizer' => ['desk organizer', ['organizer', 'desk', 'pencil']],
        'tencere seti' => ['cookware pots', ['pot', 'pan', 'cookware']],
        'vazo' => ['ceramic vase', ['vase']],
        'avize' => ['modern chandelier', ['chandelier', 'ceiling', 'lamp']],
        'yoga matı' => ['yoga mat', ['yoga', 'mat']],
        'dambıl seti' => ['dumbbells', ['dumbbell', 'weight']],
        'matarası' => ['steel water bottle', ['bottle', 'flask', 'thermos']],
        'tıraş makinesi' => ['electric shaver', ['shaver', 'razor']],
        'saç kurutma' => ['hair dryer', ['hair', 'dryer', 'drier']],
        'oyun kolu' => ['gamepad controller', ['gamepad', 'controller', 'joystick']],
        'kulaklık standı rgb' => ['headphone stand', ['stand', 'headphone']],
        'gitar' => ['acoustic guitar', ['guitar']],
        'mikrofon' => ['studio microphone', ['microphone', 'mic']],
        'tripod' => ['camera tripod', ['tripod']],
        'aksiyon kamera' => ['action camera', ['camera', 'gopro']],
        'soundbar' => ['soundbar speaker', ['soundbar', 'speaker', 'sound']],
        'projektör' => ['video projector', ['projector', 'beamer']],
        'masaj aleti' => ['massage device', ['massage']],
        'tartı' => ['bathroom scale', ['scale', 'weighing']],
    ];

    /** Başlıkta bu kelimeler geçen dosyalar asla seçilmez */
    private const YASAKLI = [
        'amiga', 'commodore', 'retro', 'vintage', '1980', '1970', '1990', 'crt', 'floppy',
        'screenshot', 'diagram', 'schematic', 'logo', 'icon', 'map', 'chart', 'graph',
        'drawing', 'painting', 'poster', 'flag', 'coat', 'statue', 'museum', 'typewriter',
        'cover', ' booklet', 'manual', 'patent', 'sketch', 'clipart', 'cartoon', 'pixel',
    ];

    /**
     * İnsan gözüyle kürasyonlanmış kesin Commons dosyaları (ürün adı → dosya başlığı).
     * Var olan ürünler için arama yerine bu harita kullanılır.
     */
    private const DOSYALAR = [
        'Kablosuz Mouse MX-2200 Sessiz Tıklama' => 'File:A black wireless computer mouse.jpg',
        'Gaming Mouse RGB 8000 DPI 7 Tuş' => 'File:2023 Mysz komputerowa Logitech G903 Lightspeed.jpg',
        '27″ QHD Monitör 165Hz 1ms IPS' => 'File:Computer monitor.jpg',
        '24″ IPS Full HD Monitör HDMI+VGA' => 'File:LG L194WT-SF LCD monitor.jpg',
        'USB-C Hub 7 Port 4K HDMI + SD Kart' => 'File:Powered USB hub with 7 ports.jpg',
        'Web Kamera 1080p Otomatik Odak Mikrofonlu' => 'File:USB webcam for PC.jpg',
        'Laptop Çantası 15.6″ Su Geçirmez Fermuarlı' => 'File:Laptop bag.jpg',
        'Mekanik Klavye TKL RGB Kırmızı Switch' => 'File:Beautiful Mechanical Keyboard.jpg',
        'Bluetooth Kulaklık ANC 40 Saat Siyah' => 'File:Bose QuietComfort 25 Acoustic Noise Cancelling Headphones with Carry Case.jpg',
        'Powerbank 20.000 mAh 22.5W Hızlı Şarj' => 'File:Charging a smartphone with a USB power bank.jpg',
        'USB-C Hızlı Şarj Kablosu 2 m örgü' => 'File:Black charging cable type USB-C plugs 01.jpg',
        'Bluetooth Hoparlör 20W Su Geçirmez' => 'File:JBL Flip 3 bluetooth speaker (DSCF2653).jpg',
        'Spiralli Defter A4 120 Yaprak Kareli' => 'File:Spiralblock-aufgeschlagen.jpg',
        'Tükenmez Kalem 10 Lu Mavi 0.7 mm' => 'File:Parker Vector Stainless Steel Ballpoint Pen.jpg',
        'Zımba Makinesi + 24/6 Tel Seti Metal' => 'File:Stapler office.jpg',
        'Ergonomik Ofis Sandalyesi Bel Desteği' => 'File:Buerostuhl (fcm).jpg',
        'Masa Lambası LED 3 Mod Dokunmatik' => 'File:Bankers lamp (10806062373).jpg',
        'Masa Organizer 5 Bölmeli Ahap' => 'File:Organizers desk (42399682924).jpg',
        'Tencere Seti 7 Parça Granit Kaplama' => 'File:Brass Sauce Pans and Pots in Italian Restaurant kitchen - panoramio.jpg',
        'Seramik Vazo 24 cm El Yapımı' => 'File:A cut glass vase on white.jpg',
        'LED Avize Modern 60 cm Üçlü' => 'File:Geometric white pendant lamp chandelier.jpg',
        'Yoga Matı 6 mm TPE Kaymaz' => 'File:Yoga mat and water bottle in a living room.jpg',
        'Dambıl Seti 2×5 kg Silikon Kaplama' => 'File:Colorful dumbbells of various sizes.jpg',
        'Çelik Spor Matarası 750 ml Termos' => 'File:Thermos flask Termoverken.jpg',
        'Şarjlı Tıraş Makinesi Su Geçirmez 3 Başlık' => 'File:2023 Golarka Philips.jpg',
        'Saç Kurutma Makinesi 2200W İyonlu' => 'File:HITACHI HAIR DRYER HD-1650.jpg',
        'Kablosuz Oyun Kolu PC/PS Titreşimli' => 'File:Nintendo-Switch-Pro-Controller-FL.jpg',
        'Kulaklık Standı RGB Alüminyum' => 'File:Logitech G733 and Twitch Headphone Stand at K7 Entertainment live studio 20251004.jpg',
        'Akustik Gitar 41″ Başlangıç Seti Kılıf+Pena' => 'File:00 Walden Acoustic Guitar D310e 01.jpg',
        'USB Mikrofon Podcast Seti Masa Standlı' => 'File:Rode NT-USB Mini microphone 001.jpg',
        'Tripod 165 cm Alüminyum Telefon Tutuculu' => 'File:(Photography equipment Tripod Photo Camera Tripod photograph in a studio).jpg',
        'Aksiyon Kamera 4K 30fps Su Geçirmez Kutulu' => 'File:GoPro Hero 10 Black with battery door open.jpg',
        'Soundbar 2.1 Bluetooth 200W Subwooferlı' => 'File:SAMSUNG SOUNDBAR HW-J250.jpg',
        'Projektör Full HD 1080p 6500 Lümen' => 'File:InFocus IN34.jpg',
        'Boynuz Masaj Aleti Shiatsu Şarjlı' => 'File:Using a percution massage gun to do a foot massage.jpg',
        'Dijital Tartı Cam 180 kg Şarjlı' => 'File:Dretec digital bathroom scale BS-164.jpg',
    ];

    public function handle(): int
    {
        // Kürasyonlanmış harita üzerinden çalışır: her ürün için kesin Commons dosyası indirilir
        $basarili = 0;
        $basarisiz = [];

        foreach (self::DOSYALAR as $urunAdi => $dosyaBasligi) {
            $urun = Product::where('name', $urunAdi)->with('images')->first();
            if (! $urun || $urun->images->isEmpty()) {
                $basarisiz[] = $urunAdi . ' (ürün yok)';
                continue;
            }

            $indirilen = $this->indirDosya($dosyaBasligi, $urun->id);
            if (! $indirilen) {
                $basarisiz[] = $urunAdi;
                continue;
            }

            $ana = $urun->images->first(fn ($i) => $i->is_primary) ?? $urun->images->first();
            $ana->update(['path' => $indirilen]);

            $basarili++;
            $this->line("  ✓ {$urunAdi}");
        }

        $this->info("Tamamlandı: {$basarili} ürün güncellendi." . ($basarisiz ? ' İndirilemeyen: ' . implode(', ', $basarisiz) : ''));

        return self::SUCCESS;
    }

    /**
     * Kesin Commons dosya başlığıyla (File:…) fotoğrafı indirir: 900px thumb,
     * JPEG/PNG/WebP imza doğrulamalı.
     */
    private function indirDosya(string $dosyaBasligi, int $urunId): ?string
    {
        try {
            $sorgu = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'titles' => $dosyaBasligi,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url|mime|size',
                    'iiurlwidth' => 900,
                    'format' => 'json',
                ]);

            $sayfalar = collect($sorgu->json('query.pages') ?? []);
            $bilgi = $sayfalar->first()['imageinfo'][0] ?? null;

            if (! $bilgi || empty($bilgi['thumburl'])) {
                return null;
            }

            $foto = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])
                ->get($bilgi['thumburl']);
            $govde = $foto->body();

            // İmza denetimi: JPEG (FFD8), PNG (89504E47), WebP (RIFF…WEBP)
            $uzanti = match (true) {
                substr($govde, 0, 2) === "\xFF\xD8" => 'jpg',
                substr($govde, 0, 4) === "\x89PNG" => 'png',
                strlen($govde) > 12 && substr($govde, 8, 4) === 'WEBP' => 'webp',
                default => null,
            };

            if (! $foto->successful() || strlen($govde) < 8192 || $uzanti === null) {
                return null;
            }

            $dosyaAdi = 'urun_' . $urunId . '_' . Str::random(8) . '.' . $uzanti;
            Storage::disk('local')->put('product-images/' . $dosyaAdi, $govde);

            return 'product-images/' . $dosyaAdi;
        } catch (\Throwable $e) {
            $this->line("    ✕ ({$dosyaBasligi}): " . substr($e->getMessage(), 0, 100));

            return null;
        }
    }

    /** Ürün adına göre arama terimleri + başlıkta zorunlu kelimeler */
    private function urunEslesmesi(string $ad): array
    {
        $ad = mb_strtolower($ad);

        foreach (self::ESLEME as $ipucu => [$arama, $baslikKelimeler]) {
            if (Str::contains($ad, $ipucu)) {
                return ['aramalar' => [$arama, 'office technology'], 'baslikKelimeler' => $baslikKelimeler];
            }
        }

        return ['aramalar' => ['office desk technology'], 'baslikKelimeler' => ['office', 'desk', 'computer', 'technology']];
    }

    /**
     * Commons araması yapar; başlık filtresinden geçen ilk uygun fotoğrafı indirir.
     * Geçerli JPEG (≥8 KB) değilse null döner.
     */
    private function indir(string $arama, array $baslikKelimeler, int $urunId): ?string
    {
        try {
            $aramaYaniti = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'generator' => 'search',
                    'gsrsearch' => 'filetype:bitmap ' . $arama,
                    'gsrnamespace' => 6,
                    'gsrlimit' => 10,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url|mime|size',
                    'iiurlwidth' => 900,
                    'format' => 'json',
                ]);

            $sayfalar = collect($aramaYaniti->json('query.pages') ?? []);

            foreach ($sayfalar as $sayfa) {
                $baslik = mb_strtolower((string) ($sayfa['title'] ?? ''));
                $bilgi = $sayfa['imageinfo'][0] ?? null;

                if (! $bilgi) {
                    continue;
                }

                // Yasaklı kelime varsa ele
                foreach (self::YASAKLI as $yasak) {
                    if (Str::contains($baslik, $yasak)) {
                        continue 2;
                    }
                }

                // Başlıkta ürünle ilgili kelime aranır (arama teriminin anlamlı kelimeleri)
                $ilgili = false;
                foreach ($baslikKelimeler as $kelime) {
                    if (Str::contains($baslik, $kelime)) {
                        $ilgili = true;
                        break;
                    }
                }
                if (! $ilgili) {
                    continue;
                }

                if (! str_starts_with((string) ($bilgi['mime'] ?? ''), 'image/') || (int) ($bilgi['width'] ?? 0) < 500) {
                    continue;
                }

                $thumburl = $bilgi['thumburl'] ?? null;
                if (! $thumburl) {
                    continue;
                }

                $foto = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])
                    ->get($thumburl);
                $govde = $foto->body();

                // İmza denetimi: JPEG (FFD8), PNG (89504E47), WebP (RIFF…WEBP)
                $imza = substr($govde, 0, 2);
                $uzanti = match (true) {
                    $imza === "\xFF\xD8" => 'jpg',
                    substr($govde, 0, 4) === "\x89PNG" => 'png',
                    substr($govde, 8, 4) === 'WEBP' => 'webp',
                    default => null,
                };

                if (! $foto->successful() || strlen($govde) < 8192 || $uzanti === null) {
                    continue;
                }

                $dosyaAdi = 'urun_' . $urunId . '_' . Str::random(8) . '.' . $uzanti;
                Storage::disk('local')->put('product-images/' . $dosyaAdi, $govde);

                return 'product-images/' . $dosyaAdi;
            }

            return null;
        } catch (\Throwable $e) {
            $this->line("    ✕ ({$arama}): " . substr($e->getMessage(), 0, 100));

            return null;
        }
    }
}
