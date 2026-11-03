<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToCompany, RecordsActivity, SoftDeletes;

    public const CATEGORIES = ['Kira', 'Elektrik', 'Su', 'Doğalgaz', 'Internet', 'Maaş', 'Vergi', 'Lojistik', 'Diğer'];

    protected $fillable = [
        'company_id',
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_type',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
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
        return $this->category.' — '.number_format((float) $this->amount, 2, ',', '.').' ₺ ('.$this->expense_date->format('d.m.Y').')';
    }
}
