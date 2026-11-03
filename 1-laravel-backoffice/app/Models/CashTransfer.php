<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class CashTransfer extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = [
        'company_id',
        'amount',
        'direction',
        'description',
        'transfer_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
        ];
    }

    public function isToBank(): bool
    {
        return $this->direction === 'nakit_havale';
    }

    public function directionLabel(): string
    {
        return $this->isToBank() ? 'Kasa → Banka' : 'Banka → Kasa';
    }
}
