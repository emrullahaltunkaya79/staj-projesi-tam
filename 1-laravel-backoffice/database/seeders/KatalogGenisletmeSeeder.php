<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Kategori genişletme: her ana kategoriye alt kategorili yeni ürünler ekler.
 * Ürün görselleri GD ile üretilir (marka + ad + kategori rengi kart tasarımı),
 * mevcut ürünlere isimden alt kategori atanır. Idempotent: aynı adla ürün varsa atlar.
 *
 * Çalıştırma: php artisan db:seed --class=KatalogGenisletmeSeeder
 */
class KatalogGenisletmeSeeder extends Seeder
{
    /** Kategori → kart vurgu rengi (R,G,B) */
    private const RENKLER = [
        'Bilgisayar' => [37, 99, 235],
        'Elektronik' => [6, 182, 212],
        'Kırtasiye' => [22, 163, 74],
        'Ofis' => [107, 114, 128],
        'Ev & Yaşam' => [217, 119, 6],
        'Oyun & Eğlence' => [139, 92, 246],
        'Spor & Outdoor' => [22, 132, 69],
        'Kişisel Bakım' => [236, 72, 153],
        'Müzik' => [217, 70, 70],
        'Fotoğraf & Kamera' => [30, 41, 59],
        'Ev Sineması' => [79, 70, 229],
        'Wellness & Sağlık' => [13, 148, 136],
    ];

    /** Mevcut ürün adlarından alt kategori çıkarımı (regex → alt kategori) */
    private const MEVCUT_ESLEME = [
        '/klavye/i' => 'Klavye',
        '/mouse/i' => 'Mouse',
        '/monitör|ekran/i' => 'Monitör',
        '/kulaklık/i' => 'Kulaklık',
        '/hoparlör/i' => 'Hoparlör',
        '/defter/i' => 'Defter',
        '/kalem/i' => 'Kalem',
        '/z[ıi]mba/i' => 'Zımba',
        '/silgi|kalemtıraş/i' => 'Silgi & Kalemtıraş',
        '/sandalye/i' => 'Sandalye',
        '/masa\b/i' => 'Masalar',
        '/tripod/i' => 'Tripod',
        '/laptop|notebook|bilgisayar/i' => 'Dizüstü',
        '/kamera/i' => 'Kamera',
        '/gitar/i' => 'Gitar',
        '/mikrofon/i' => 'Mikrofon',
        '/projeksiyon|projektör/i' => 'Projektör',
        '/yoga|matara|dambıl/i' => 'Yoga & Fitness',
        '/tencere|tava|kitchen|fırça/i' => 'Mutfak',
        '/tartı/i' => 'Sağlık Ürünleri',
        '/powerbank/i' => 'Powerbank',
        '/çanta|bag/i' => 'Çanta',
        '/kablosuz\s*şarj|şarj/i' => 'Şarj Aksesuarı',
    ];

    /** [kategori, alt kategori, ad, marka, model, alış, satış, indirim%, stok] */
    private const YENI_URUNLER = [
        // Bilgisayar
        ['Bilgisayar', 'Mouse', 'Kablosuz Mouse MX-2200 Sessiz Tıklama', 'Logitech', 'MX-2200', 320, 549, 12, 30],
        ['Bilgisayar', 'Mouse', 'Gaming Mouse RGB 8000 DPI 7 Tuş', 'SteelSeries', 'Rival-3', 410, 699, 0, 22],
        ['Bilgisayar', 'Monitör', '27″ QHD Monitör 165Hz 1ms IPS', 'Samsung', 'LS27CG510', 3200, 4899, 15, 14],
        ['Bilgisayar', 'Monitör', '24″ IPS Full HD Monitör HDMI+VGA', 'LG', '24MP400', 2150, 2949, 0, 18],
        ['Bilgisayar', 'USB Hub', 'USB-C Hub 7 Port 4K HDMI + SD Kart', 'Ugreen', 'CM498', 480, 789, 10, 26],
        ['Bilgisayar', 'Web Kamera', 'Web Kamera 1080p Otomatik Odak Mikrofonlu', 'Logitech', 'C920s', 690, 1049, 0, 16],
        ['Bilgisayar', 'Çanta', 'Laptop Çantası 15.6″ Su Geçirmez Fermuarlı', 'Targus', 'CN600', 260, 429, 20, 34],
        ['Bilgisayar', 'Klavye', 'Mekanik Klavye TKL RGB Kırmızı Switch', 'Corsair', 'K60 RGB', 780, 1249, 10, 15],
        // Elektronik
        ['Elektronik', 'Kulaklık', 'Bluetooth Kulaklık ANC 40 Saat Siyah', 'Anker', 'Soundcore Q30', 890, 1399, 18, 24],
        ['Elektronik', 'Powerbank', 'Powerbank 20.000 mAh 22.5W Hızlı Şarj', 'Xiaomi', 'PB2022', 430, 679, 0, 40],
        ['Elektronik', 'Şarj Aksesuarı', 'USB-C Hızlı Şarj Kablosu 2 m örgü', 'Anker', 'A8091', 89, 149, 0, 60],
        ['Elektronik', 'Hoparlör', 'Bluetooth Hoparlör 20W Su Geçirmez', 'JBL', 'Flip-Essential', 1150, 1699, 12, 19],
        // Kırtasiye
        ['Kırtasiye', 'Defter', 'Spiralli Defter A4 120 Yaprak Kareli', 'Mead', '5STAR-A4', 42, 79, 0, 90],
        ['Kırtasiye', 'Kalem', 'Tükenmez Kalem 10 Lu Mavi 0.7 mm', 'Bic', 'Cristal-10', 28, 49, 15, 120],
        ['Kırtasiye', 'Zımba', 'Zımba Makinesi + 24/6 Tel Seti Metal', 'Kangaro', 'M-10', 95, 159, 0, 45],
        // Ofis
        ['Ofis', 'Sandalye', 'Ergonomik Ofis Sandalyesi Bel Desteği', 'Siesta', 'ERGO-BEL', 1850, 2799, 12, 12],
        ['Ofis', 'Masa Ürünleri', 'Masa Lambası LED 3 Mod Dokunmatik', 'Philips', 'LED-3M', 180, 289, 0, 28],
        ['Ofis', 'Organizer', 'Masa Organizer 5 Bölmeli Ahap', 'Deskline', 'ORG-5', 210, 349, 0, 20],
        // Ev & Yaşam
        ['Ev & Yaşam', 'Mutfak', 'Tencere Seti 7 Parça Granit Kaplama', 'Emsan', 'GRANIT-7', 1490, 2299, 15, 14],
        ['Ev & Yaşam', 'Dekorasyon', 'Seramik Vazo 24 cm El Yapımı', 'Vazoline', 'VZ-24', 240, 399, 0, 25],
        ['Ev & Yaşam', 'Aydınlatma', 'LED Avize Modern 60 cm Üçlü', 'Aydınlı', 'AVZ-60', 640, 999, 10, 16],
        // Spor & Outdoor
        ['Spor & Outdoor', 'Yoga & Fitness', 'Yoga Matı 6 mm TPE Kaymaz', 'Zenfit', 'YM-TPE6', 195, 329, 0, 38],
        ['Spor & Outdoor', 'Yoga & Fitness', 'Dambıl Seti 2×5 kg Silikon Kaplama', 'Ironpro', 'DB-2X5', 380, 599, 10, 22],
        ['Spor & Outdoor', 'Matara', 'Çelik Spor Matarası 750 ml Termos', 'Karaca', 'MAT-750', 165, 269, 0, 44],
        // Kişisel Bakım
        ['Kişisel Bakım', 'Tıraş', 'Şarjlı Tıraş Makinesi Su Geçirmez 3 Başlık', 'Philips', 'S3232', 690, 1049, 15, 20],
        ['Kişisel Bakım', 'Saç Bakımı', 'Saç Kurutma Makinesi 2200W İyonlu', 'Braun', 'HD-2200', 540, 829, 0, 24],
        // Oyun & Eğlence
        ['Oyun & Eğlence', 'Konsol Aksesuar', 'Kablosuz Oyun Kolu PC/PS Titreşimli', 'GameSir', 'T4-Pro', 520, 799, 12, 26],
        ['Oyun & Eğlence', 'Konsol Aksesuar', 'Kulaklık Standı RGB Alüminyum', 'Krom', 'KNDR-RGB', 230, 379, 0, 30],
        // Müzik
        ['Müzik', 'Gitar', 'Akustik Gitar 41″ Başlangıç Seti Kılıf+Pena', 'Donner', 'DAG-41', 1450, 2199, 10, 10],
        ['Müzik', 'Mikrofon', 'USB Mikrofon Podcast Seti Masa Standlı', 'Fifine', 'AM8', 780, 1199, 0, 18],
        // Fotoğraf & Kamera
        ['Fotoğraf & Kamera', 'Tripod', 'Tripod 165 cm Alüminyum Telefon Tutuculu', 'Manfrotto', 'TP-165', 640, 989, 12, 17],
        ['Fotoğraf & Kamera', 'Aksiyon Kamera', 'Aksiyon Kamera 4K 30fps Su Geçirmez Kutulu', 'Nexgo', 'AK-4K', 1290, 1949, 15, 13],
        // Ev Sineması
        ['Ev Sineması', 'Soundbar', 'Soundbar 2.1 Bluetooth 200W Subwooferlı', 'JBL', 'SB-2.1', 2390, 3499, 15, 11],
        ['Ev Sineması', 'Projektör', 'Projektör Full HD 1080p 6500 Lümen', 'BenQ', 'PJ-1080', 3450, 4999, 10, 9],
        // Wellness & Sağlık
        ['Wellness & Sağlık', 'Masaj', 'Boynuz Masaj Aleti Shiatsu Şarjlı', 'Restflix', 'MSJ-Neck', 480, 749, 20, 21],
        ['Wellness & Sağlık', 'Sağlık Ürünleri', 'Dijital Tartı Cam 180 kg Şarjlı', 'Arzum', 'TRT-Glass', 260, 419, 0, 32],
    ];

    public function run(): void
    {
        $company = 1;
        $uretilenGorsel = 0;

        // 1) Mevcut ürünlere isimden alt kategori ata
        foreach (Product::with('category')->get() as $urun) {
            if ($urun->alt_kategori) {
                continue;
            }
            foreach (self::MEVCUT_ESLEME as $kalip => $alt) {
                if (preg_match($kalip, $urun->name)) {
                    $urun->update(['alt_kategori' => $alt]);
                    break;
                }
            }
        }

        // 2) Yeni ürünler + üretilen görseller
        foreach (self::YENI_URUNLER as [$kategoriAd, $alt, $ad, $marka, $model, $alis, $satis, $indirim, $stok]) {
            $kategori = Category::where('name', $kategoriAd)->first();
            if (! $kategori || Product::where('name', $ad)->exists()) {
                continue;
            }

            $urun = Product::create([
                'company_id' => $company,
                'category_id' => $kategori->id,
                'alt_kategori' => $alt,
                'code' => 'KATG-' . str_pad((string) (Product::max('id') + 1), 4, '0', STR_PAD_LEFT),
                'name' => $ad,
                'brand' => $marka,
                'model' => $model,
                'unit' => 'adet',
                'purchase_price' => $alis,
                'sale_price' => $satis,
                'discount_percent' => $indirim,
                'stock_quantity' => $stok,
            ]);

            $yol = $this->gorselUret($urun, $kategoriAd, $marka, $indirim > 0 ? '%' . $indirim . ' İNDİRİM' : 'YENİ');
            if ($yol) {
                ProductImage::create([
                    'company_id' => $company,
                    'product_id' => $urun->id,
                    'path' => $yol,
                    'is_primary' => true,
                    'sort' => 1,
                ]);
                $uretilenGorsel++;
            }
        }

        $this->command?->info("Katalog genişletildi: yeni görsel üretimi {$uretilenGorsel}, toplam ürün " . Product::count());
    }

    /**
     * GD ile ürün kartı görseli üretir: açık zemin, kategori renginde büyük baş harf rozeti,
     * marka + ürün adı + rozet şeridi. storage/app/private/product-images altına JPG yazar.
     */
    private function gorselUret(Product $urun, string $kategoriAd, string $marka, string $rozetMetni): ?string
    {
        $g = 800;
        $resim = imagecreatetruecolor($g, $g);

        $renk = self::RENKLER[$kategoriAd] ?? [55, 65, 81];
        $zemin = imagecolorallocate($resim, 244, 244, 244);
        $koyu = imagecolorallocate($resim, 17, 17, 17);
        $soluk = imagecolorallocate($resim, 130, 130, 130);
        $vurgu = imagecolorallocate($resim, ...$renk);
        $vurguAcik = imagecolorallocatealpha($resim, ...array_slice([...$renk, 40], 0, 4));

        imagefilledrectangle($resim, 0, 0, $g, $g, $zemin);
        // köşe şerit
        imagefilledrectangle($resim, 0, $g - 26, $g, $g, imagecolorallocate($resim, ...array_slice([...$renk, 0], 0, 3)));

        // büyük baş harf rozeti
        $harf = mb_strtoupper(mb_substr($urun->name, 0, 1));
        imagefilledellipse($resim, $g / 2, $g / 2 - 90, 340, 340, $vurguAcik);
        imageellipse($resim, $g / 2, $g / 2 - 90, 340, 340, $vurgu);
        $font = 'C:\\Windows\\Fonts\\arialbd.ttf';
        if (! is_file($font)) {
            $font = 'C:\\Windows\\Fonts\\arial.ttf';
        }
        $kutu = imagettfbbox(170, 0, $font, $harf);
        imagettftext($resim, 170, 0, (int) ($g / 2 - ($kutu[2] - $kutu[0]) / 2), (int) ($g / 2 - 90 + 60), $vurgu, $font, $harf);

        // rozet şeridi
        $rozetKutu = imagettfbbox(28, 0, $font, $rozetMetni);
        $rozetGen = (int) ($rozetKutu[2] - $rozetKutu[0]) + 36;
        imagefilledrectangle($resim, (int) (($g - $rozetGen) / 2), 64, (int) (($g + $rozetGen) / 2), 116, $vurgu);
        imagettftext($resim, 28, 0, (int) (($g - $rozetGen) / 2 + 18), 100, imagecolorallocate($resim, 255, 255, 255), $font, $rozetMetni);

        // marka
        $markaKutu = imagettfbbox(30, 0, $font, $marka);
        imagettftext($resim, 30, 0, (int) ($g / 2 - ($markaKutu[2] - $markaKutu[0]) / 2), $g - 150, $soluk, $font, $marka);

        // ürün adı — 2 satıra kadar sarmala
        $satirlar = $this->metinSarmala($urun->name, 36, $g - 90, $font);
        $y = $g - 100;
        foreach (array_slice($satirlar, 0, 2) as $satir) {
            $k = imagettfbbox(36, 0, $font, $satir);
            imagettftext($resim, 36, 0, (int) ($g / 2 - ($k[2] - $k[0]) / 2), $y, $koyu, $font, $satir);
            $y -= 46;
        }

        $dosyaAdi = 'uretilen_' . md5($urun->name . microtime()) . '.jpg';
        $mutlak = storage_path('app/private/product-images/' . $dosyaAdi);
        if (! is_dir(dirname($mutlak))) {
            mkdir(dirname($mutlak), 0775, true);
        }
        $tamam = imagejpeg($resim, $mutlak, 88);
        imagedestroy($resim);

        return $tamam ? 'product-images/' . $dosyaAdi : null;
    }

    /** Metni verilen piksel genişliğine sığacak satırlara böler (imagettfbbox ile gerçek ölçüm). */
    private function metinSarmala(string $metin, int $punto, int $maksGenislik, string $font): array
    {
        $satirlar = [];
        $mevcut = '';
        foreach (explode(' ', $metin) as $kelime) {
            $aday = $mevcut === '' ? $kelime : $mevcut . ' ' . $kelime;
            $kutu = imagettfbbox($punto, 0, $font, $aday);
            if (($kutu[2] - $kutu[0]) > $maksGenislik && $mevcut !== '') {
                $satirlar[] = $mevcut;
                $mevcut = $kelime;
            } else {
                $mevcut = $aday;
            }
        }
        if ($mevcut !== '') {
            $satirlar[] = $mevcut;
        }

        return $satirlar;
    }
}
