<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'type',
        'contact_id',
        'invoice_no',
        'verification_code',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'refunded_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
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

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getAuditName(): string
    {
        return $this->invoice_no;
    }

    /**
     * QR kod için doğrulama URL'i üretir.
     */
    public function verificationUrl(): string
    {
        return route('fatura.dogrula', $this->verification_code);
    }

    public function isSale(): bool
    {
        return $this->type === 'satis';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'satis' => 'Satış Faturası',
            'alis' => 'Alış Faturası',
            default => 'İade Faturası',
        };
    }

    public function typeShortLabel(): string
    {
        return match ($this->type) {
            'satis' => 'Satış',
            'alis' => 'Alış',
            default => 'İade',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'satis' => 'badge-green',
            'alis' => 'badge-red',
            default => 'badge-amber',
        };
    }

    /**
     * Bu faturayı tersine çeviren iade faturaları.
     */
    public function refunds()
    {
        return $this->hasMany(self::class, 'refunded_invoice_id');
    }

    /**
     * İade faturasıysa, iade ettiği asıl fatura.
     */
    public function refundOf()
    {
        return $this->belongsTo(self::class, 'refunded_invoice_id');
    }

    /**
     * Fatura toplamının Türkçe yazıyla karşılığı ("Yalnız ... Türk Lirası" satırı).
     */
    public function amountInWords(): string
    {
        return ucfirst(\App\Support\Money::toTurkishWords((float) $this->total));
    }
}
