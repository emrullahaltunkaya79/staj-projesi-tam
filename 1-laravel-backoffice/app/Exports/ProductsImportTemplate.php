<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Ürün içe aktarma şablonu: beklenen sütunları ve örnek satırı içerir.
 */
class ProductsImportTemplate implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['Kod', 'Ürün', 'Birim', 'Alış Fiyatı', 'Satış Fiyatı', 'Stok'];
    }

    public function array(): array
    {
        return [
            ['URN-010', 'Örnek Ürün', 'adet', 100, 180, 25],
        ];
    }
}
