<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCompany, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'code',
        'barcode',
        'name',
        'unit',
        'purchase_price',
        'sale_price',
        'discount_percent',
        'stock_quantity',
        'reserved_quantity',
    ];

    /** İndirim uygulanmış nihai satış fiyatı. */
    public function finalPrice(): float
    {
        $indirim = (int) ($this->discount_percent ?? 0);

        return $indirim > 0
            ? round((float) $this->sale_price * (100 - $indirim) / 100, 2)
            : (float) $this->sale_price;
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Ürün stokla açılırsa "açılış" hareketi kaydet ve stoğu ilk depoya ata
        static::created(function ($product) {
            if ($product->stock_quantity > 0) {
                StockMovement::record(
                    $product,
                    'acilis',
                    'giris',
                    (float) $product->stock_quantity,
                    (float) $product->stock_quantity,
                    null,
                    'Açılış stoğu'
                );

                // Varsayılan depoya ata (ilk aktif depo)
                $warehouse = Warehouse::where('company_id', $product->company_id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();

                if ($warehouse) {
                    $warehouse->adjustStock($product->id, $product->stock_quantity);
                }
            }
        });

        // Stok elle değiştirilirse "düzeltme" hareketi kaydet
        // (servisler stogu Query Builder ile değiştirdiğinden bu olay tetiklenmez)
        static::updated(function ($product) {
            if ($product->wasChanged('stock_quantity')) {
                $old = (int) $product->getOriginal('stock_quantity');
                $new = (int) $product->stock_quantity;

                if ($new !== $old) {
                    StockMovement::record(
                        $product,
                        'duzeltme',
                        $new > $old ? 'giris' : 'cikis',
                        (float) abs($new - $old),
                        (float) $new,
                        null,
                        'Manuel stok düzeltmesi'
                    );
                }
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    /**
     * Ana görselin data-URI'si (yoksa null) — API ve thumb için.
     */
    public function primaryImageUri(): ?string
    {
        $primary = $this->images->firstWhere('is_primary') ?? $this->images->first();

        return $primary?->dataUri();
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot('stock_quantity')
            ->withTimestamps();
    }

    /**
     * Ürünün tüm depolardaki toplam stoğu.
     */
    public function totalWarehouseStock(): int
    {
        return (int) $this->warehouses()->sum('stock_quantity');
    }

    /**
     * Satılabilir (müsait) stok = fiziksel stok − rezerve edilen.
     */
    public function availableQuantity(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return $this->stock_quantity < $threshold;
    }
}
