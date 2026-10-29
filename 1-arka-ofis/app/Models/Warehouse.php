<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use BelongsToCompany, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_warehouse')
            ->withPivot('stock_quantity')
            ->withTimestamps();
    }

    /**
     * Belirli bir üründen bu depodaki stok miktarı.
     */
    public function stockOf(int $productId): int
    {
        return (int) ($this->stocks()->where('product_id', $productId)->value('stock_quantity') ?? 0);
    }

    /**
     * Depo stogunu artır/azalt (pivot kaydı yoksa oluşturur).
     */
    public function adjustStock(int $productId, int $change): void
    {
        $stock = WarehouseStock::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $this->id],
            ['company_id' => $this->company_id, 'stock_quantity' => 0]
        );

        $stock->increment('stock_quantity', $change);
    }
}
