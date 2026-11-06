<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'company_id',
        'product_id',
        'name',
        'type',
        'sku',
        'stock_quantity',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    private const BEDEN_SIRASI = ['XXS', 'XS', 'S', 'S/M', 'M', 'M/L', 'L', 'L/XL', 'XL', 'XXL', '3XL'];

    /** Listeleme sırası: bedenler küçükten büyüğe (S → XL, 36 → 44), renkler ada göre. */
    public function siraAnahtari(): string
    {
        if ($this->type !== 'beden') {
            return 'd'.$this->name;
        }

        $sira = array_search(mb_strtoupper($this->name), self::BEDEN_SIRASI, true);
        if ($sira !== false) {
            return sprintf('a%02d', $sira);
        }

        return is_numeric($this->name) ? sprintf('b%07.2f', (float) $this->name) : 'c'.$this->name;
    }
}
