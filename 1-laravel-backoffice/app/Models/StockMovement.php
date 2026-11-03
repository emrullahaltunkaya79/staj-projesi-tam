<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'product_name',
        'type',
        'direction',
        'quantity',
        'stock_after',
        'invoice_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stock_after' => 'decimal:2',
        ];
    }

    /**
     * Tek noktadan stok hareketi kaydı: tüm servisler ve model olayları burayı kullanır.
     */
    public static function record(Product $product, string $type, string $direction, float $quantity, float $stockAfter, ?Invoice $invoice = null, ?string $description = null, ?int $warehouseId = null): self
    {
        return static::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'product_name' => $product->name,
            'type' => $type,
            'direction' => $direction,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'invoice_id' => $invoice?->id,
            'description' => $description,
        ]);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isIncoming(): bool
    {
        return $this->direction === 'giris';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'acilis' => 'Açılış',
            'alis' => 'Alış',
            'satis' => 'Satış',
            'iade' => 'İade',
            default => 'Düzeltme',
        };
    }
}
