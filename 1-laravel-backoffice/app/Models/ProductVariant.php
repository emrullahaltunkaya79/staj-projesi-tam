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
        'renk',
        'beden',
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

    /** Listeleme sırası: kombiler renk→beden; bedenler küçükten büyüğe; renkler ada göre. */
    public function siraAnahtari(): string
    {
        if ($this->type === 'kombi') {
            $b = array_search(mb_strtoupper((string) $this->beden), self::BEDEN_SIRASI, true);
            $b = $b === false ? 99 : $b;

            return sprintf('a%02d%s', $b, mb_strtolower((string) $this->renk));
        }

        if ($this->type === 'beden') {
            $sira = array_search(mb_strtoupper($this->name), self::BEDEN_SIRASI, true);
            if ($sira !== false) {
                return sprintf('a%02d', $sira);
            }

            return is_numeric($this->name) ? sprintf('b%07.2f', (float) $this->name) : 'c'.$this->name;
        }

        return 'd'.$this->name;
    }
}
