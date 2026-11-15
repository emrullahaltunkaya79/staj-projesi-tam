<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToCompany, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'phone',
        'email',
        'tax_number',
        'address',
        'balance',
        'sadakat_puani',
        'dogum_tarihi',
        'referans_kodu',
        'referans_kazandi',
        'plus_bitis',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'sadakat_puani' => 'integer',
            'dogum_tarihi' => 'date',
            'referans_kazandi' => 'boolean',
            'plus_bitis' => 'datetime',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isCustomer(): bool
    {
        return $this->type === 'musteri';
    }

    public function typeLabel(): string
    {
        return $this->isCustomer() ? 'Müşteri' : 'Tedarikçi';
    }
}
