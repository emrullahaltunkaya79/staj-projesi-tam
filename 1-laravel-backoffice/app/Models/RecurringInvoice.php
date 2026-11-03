<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class RecurringInvoice extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = [
        'company_id',
        'contact_id',
        'title',
        'day_of_month',
        'tax_rate',
        'notes',
        'items',
        'is_active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'date',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Bu ay için fatura kesilmesi gerekiyor mu?
     * (Ayın günü geldiyse ve bu ay daha önce kesilmediyse)
     */
    public function isDueToday(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->last_run_at?->isSameMonth(now()) && $this->last_run_at->isSameYear(now())) {
            return false;
        }

        return now()->day >= $this->day_of_month;
    }
}
