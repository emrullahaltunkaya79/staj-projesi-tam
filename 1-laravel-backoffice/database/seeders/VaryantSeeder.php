<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Demo varyantları: renk seçenekli kulaklık/kablo ürünleri, beden seçenekli mouse
 * ve KOMBİ (renk + beden birlikte) akıllı saat/bileklik ürünleri.
 * Ürünler adıyla bulunur; tekrar çalıştırmak güvenlidir.
 */
class VaryantSeeder extends Seeder
{
    /** renk | beden | kombi [renk, beden] */
    private const VARYANTLAR = [
        'Kablosuz Kulaklık Pro' => ['renk', [['Siyah', null, 6], ['Beyaz', null, 0], ['Gri', null, 4]]],
        'Eldivenli Mouse Pad XL' => ['renk', [['Siyah', null, 11], ['Antrasit', null, 5]]],
        'Kulak İçi Kulaklık Pro' => ['renk', [['Siyah', null, 15], ['Pembe', null, 3], ['Beyaz', null, 9]]],
        'Bluetooth Kulaklık TWS' => ['renk', [['Siyah', null, 10], ['Beyaz', null, 5]]],
        'USB-C Kablosu 100W 2m' => ['renk', [['Gümüş', null, 6], ['Siyah', null, 12]]],
        'Akıllı Saat Sport' => ['kombi', [
            ['Siyah', 'S/M', 4], ['Siyah', 'M/L', 3],
            ['Beyaz', 'S/M', 2], ['Beyaz', 'M/L', 0],
        ]],
        'Akıllı Bileklik Fit' => ['kombi', [
            ['Siyah', 'S', 3], ['Siyah', 'M', 5],
            ['Beyaz', 'S', 2], ['Beyaz', 'M', 4],
        ]],
        'RGB Mouse 16000 DPI' => ['beden', [['M', null, 8], ['L', null, 7], ['XL', null, 4]]],
    ];

    public function run(): void
    {
        foreach (self::VARYANTLAR as $urunAdi => [$tur, $secenekler]) {
            $urun = Product::where('name', $urunAdi)->first();
            if (! $urun) {
                continue;
            }

            // Kombi üründe eski varyantlar temizlenir — kombinasyonlar her koşmada taze kurulur
            ProductVariant::where('product_id', $urun->id)->delete();

            foreach ($secenekler as [$renk, $beden, $stok]) {
                $ad = $tur === 'kombi' ? "{$renk} / {$beden}" : $renk;

                ProductVariant::create([
                    'company_id' => $urun->company_id,
                    'product_id' => $urun->id,
                    'name' => $ad,
                    'type' => $tur,
                    'renk' => $tur === 'kombi' ? $renk : ($tur === 'renk' ? $ad : null),
                    'beden' => $tur === 'kombi' ? $beden : ($tur === 'beden' ? $ad : null),
                    'stock_quantity' => $stok,
                ]);
            }
        }
    }
}
