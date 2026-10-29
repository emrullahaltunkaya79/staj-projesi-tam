<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Stok sayım listesi: mevcut stokla birlikte boş "Sayılan" ve "Fark"
 * sütunları içeren çıktı — sayım sonrası doldurulmak üzere yazdırılır.
 */
class StockCountExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Product::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Kod', 'Ürün', 'Birim', 'Sistemdeki Stok', 'Sayılan Adet', 'Fark'];
    }

    public function map($product): array
    {
        return [
            $product->code,
            $product->name,
            $product->unit,
            (int) $product->stock_quantity,
            '',
            '',
        ];
    }
}
