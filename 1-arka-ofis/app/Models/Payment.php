<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = [
        'company_id',
        'contact_id',
        'amount',
        'payment_type',
        'direction',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function isCash(): bool
    {
        return $this->payment_type === 'nakit';
    }

    public function typeLabel(): string
    {
        return $this->isCash() ? 'Nakit' : 'Havale';
    }

    public function getAuditName(): string
    {
        return $this->directionLabel().' — '.number_format((float) $this->amount, 2, ',', '.').' ₺';
    }

    public function isCollection(): bool
    {
        return $this->direction !== 'odeme';
    }

    public function directionLabel(): string
    {
        return $this->isCollection() ? 'Tahsilat' : 'Tedarikçi Ödemesi';
    }
}
