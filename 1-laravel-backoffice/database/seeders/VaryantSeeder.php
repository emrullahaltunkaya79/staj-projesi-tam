<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Demo varyantları: renk seçenekli kulaklık/kablo ürünleri ve beden seçenekli
 * giyilebilir/ergonomik ürünler. Ürünler adıyla bulunur; tekrar çalıştırmak güvenlidir.
 */
class VaryantSeeder extends Seeder
{
    private const VARYANTLAR = [
        'Kablosuz Kulaklık Pro' => ['renk', ['Siyah' => 6, 'Beyaz' => 0, 'Gri' => 4]],
        'Eldivenli Mouse Pad XL' => ['renk', ['Siyah' => 11, 'Antrasit' => 5]],
        'Kulak İçi Kulaklık Pro' => ['renk', ['Siyah' => 15, 'Pembe' => 3, 'Beyaz' => 9]],
        'Bluetooth Kulaklık TWS' => ['renk', ['Siyah' => 10, 'Beyaz' => 5]],
        'USB-C Kablosu 100W 2m' => ['renk', ['Gümüş' => 6, 'Siyah' => 12]],
        'Akıllı Saat Sport' => ['beden', ['S/M' => 9, 'M/L' => 7, 'L/XL' => 0]],
        'Akıllı Bileklik Fit' => ['beden', ['S' => 5, 'M' => 9, 'L' => 5]],
        'RGB Mouse 16000 DPI' => ['beden', ['M' => 8, 'L' => 7, 'XL' => 4]],
    ];

    public function run(): void
    {
        foreach (self::VARYANTLAR as $urunAdi => [$tur, $secenekler]) {
            $urun = Product::where('name', $urunAdi)->first();
            if (! $urun) {
                continue;
            }

            foreach ($secenekler as $ad => $stok) {
                ProductVariant::firstOrCreate(
                    ['product_id' => $urun->id, 'name' => $ad],
                    ['company_id' => $urun->company_id, 'type' => $tur, 'stock_quantity' => $stok],
                );
            }
        }
    }
}
