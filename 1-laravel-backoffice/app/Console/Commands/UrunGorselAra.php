<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Ürünler için Wikimedia Commons aday fotoğraf BAŞLIKLARINI listeler.
 * Seçim insan gözüyle yapılır: listeden uygun dosya başlığı seçilip
 * UrunGorselIndir::DOSYALAR haritasına yazılır.
 *
 * Çalıştırma: php artisan urun:gorsel-ara
 */
class UrunGorselAra extends Command
{
    protected $signature = 'urun:gorsel-ara';

    protected $description = 'Kart görselli ürünler için Commons aday fotoğraf başlıklarını listeler';

    private const ARAMALAR = [
        'Mouse' => ['computer mouse', 'wireless mouse'],
        'Monitör' => ['computer monitor lcd', 'flat panel display monitor'],
        'Klavye' => ['mechanical keyboard', 'computer keyboard'],
        'Kulaklık' => ['wireless headphones', 'headphones black'],
        'Powerbank' => ['power bank', 'portable charger'],
        'Kablo' => ['usb type-c cable'],
        'Hoparlör' => ['bluetooth speaker'],
        'Defter' => ['spiral notebook'],
        'Kalem' => ['ballpoint pens'],
        'Zımba' => ['stapler'],
        'Sandalye' => ['office chair'],
        'Masa Lambası' => ['desk lamp led'],
        'Organizer' => ['pen holder desk'],
        'Tencere' => ['cookware set pot'],
        'Vazo' => ['ceramic vase'],
        'Avize' => ['pendant lamp'],
        'Yoga' => ['yoga mat rolled'],
        'Dambıl' => ['dumbbells pair'],
        'Matara' => ['steel water bottle'],
        'Tıraş' => ['electric shaver'],
        'Saç Kurutma' => ['hair dryer'],
        'Oyun Kolu' => ['game controller gamepad'],
        'Kulaklık Standı' => ['headphone stand'],
        'Gitar' => ['acoustic guitar'],
        'Mikrofon' => ['podcast microphone'],
        'Tripod' => ['camera tripod'],
        'Aksiyon Kamera' => ['action camera'],
        'Soundbar' => ['soundbar'],
        'Projektör' => ['video projector'],
        'Masaj' => ['massage gun'],
        'Tartı' => ['bathroom scale'],
        'Çanta' => ['laptop bag briefcase'],
        'Web Kamera' => ['webcam'],
    ];

    public function handle(): int
    {
        $urunler = Product::whereHas('images', fn ($q) => $q->where('path', 'like', '%uretilen_%')
            ->orWhere('path', 'like', '%urun_%'))
            ->with('images')
            ->orderBy('id')
            ->get();

        foreach ($urunler as $urun) {
            $ad = mb_strtolower($urun->name);
            $arama = null;
            foreach (self::ARAMALAR as $ipucu => $terimler) {
                if (Str::contains($ad, mb_strtolower($ipucu))) {
                    $arama = $terimler;
                    break;
                }
            }
            $arama ??= ['office desk'];

            $this->line("── {$urun->id}: {$urun->name}");

            foreach ($arama as $terim) {
                try {
                    $yanit = Http::timeout(15)
                        ->withHeaders(['User-Agent' => 'HepsiBirarada-Staj/1.0 (localhost demo)'])
                        ->get('https://commons.wikimedia.org/w/api.php', [
                            'action' => 'query',
                            'generator' => 'search',
                            'gsrsearch' => 'filetype:bitmap ' . $terim,
                            'gsrnamespace' => 6,
                            'gsrlimit' => 6,
                            'prop' => 'imageinfo',
                            'iiprop' => 'url|mime|size',
                            'format' => 'json',
                        ]);

                    $sayfalar = collect($yanit->json('query.pages') ?? [])
                        ->sortBy(fn ($p) => $p['index'] ?? 99);

                    foreach ($sayfalar as $sayfa) {
                        $mime = $sayfa['imageinfo'][0]['mime'] ?? '';
                        $gen = $sayfa['imageinfo'][0]['width'] ?? 0;
                        if (! str_starts_with($mime, 'image/') || $gen < 600) {
                            continue;
                        }
                        $this->line("   [{$terim}] {$sayfa['title']}");
                    }
                } catch (\Throwable $e) {
                    $this->line("   [{$terim}] hata: " . substr($e->getMessage(), 0, 60));
                }
            }
        }

        return self::SUCCESS;
    }
}
