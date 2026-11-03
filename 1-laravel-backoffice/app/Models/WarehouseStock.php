<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $table = 'product_warehouse';

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'stock_quantity',
    ];

    protected function casts(): array
    {
        return ['stock_quantity' => 'integer'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
