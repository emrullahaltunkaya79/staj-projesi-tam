<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use BelongsToCompany, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'contact_id',
        'quote_no',
        'issue_date',
        'valid_until',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'converted_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function convertedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function getAuditName(): string
    {
        return $this->quote_no;
    }

    /**
     * Teklif toplamının Türkçe yazıyla karşılığı ("Yalnız ..." satırı).
     */
    public function amountInWords(): string
    {
        return ucfirst(\App\Support\Money::toTurkishWords((float) $this->total));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'acik' => 'Açık',
            'onaylandi' => 'Onaylandı',
            'reddedildi' => 'Reddedildi',
            default => 'Faturalandı',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'acik' => 'badge-indigo',
            'onaylandi' => 'badge-green',
            'reddedildi' => 'badge-red',
            default => 'badge-slate',
        };
    }
}
