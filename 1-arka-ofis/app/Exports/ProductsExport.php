<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Product::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Kod', 'Ürün', 'Birim', 'Alış Fiyatı (₺)', 'Satış Fiyatı (₺)', 'Stok', 'Stok Değeri (₺)'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->code,
            $product->name,
            $product->unit,
            (float) $product->purchase_price,
            (float) $product->sale_price,
            (int) $product->stock_quantity,
            round((float) $product->purchase_price * $product->stock_quantity, 2),
        ];
    }
}
