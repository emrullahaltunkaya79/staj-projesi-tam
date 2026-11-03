<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CasperPayment extends Model
{
    protected $fillable = [
        'company_id',
        'order_id',
        'memo',
        'amount_cspr',
        'amount_try',
        'network',
        'status',
        'deploy_hash',
        'expires_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cspr' => 'decimal:9',
            'amount_try' => 'decimal:2',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'waiting' && $this->expires_at->isPast();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'waiting' => 'Ödeme Bekleniyor',
            'detected' => 'Tespit Edildi (onay bekleniyor)',
            'confirmed' => 'Onaylandı',
            'expired' => 'Süre Doldu',
            default => $this->status,
        };
    }
}
