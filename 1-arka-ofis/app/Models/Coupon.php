<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'company_id', 'code', 'name', 'type', 'value',
        'min_order_total', 'usage_limit', 'used_count',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_total' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'percent' => "%{$this->value} indirim",
            'fixed' => number_format((float) $this->value, 2, ',', '.') . ' ₺ indirim',
            'free_shipping' => 'Ücretsiz kargo',
        };
    }
}
