<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = [
        'company_id',
        'contact_id',
        'warehouse_id',
        'order_no',
        'external_ref',
        'customer_email',
        'customer_name',
        'shipping_address',
        'tracking_no',
        'carrier',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'items',
        'notes',
        'paid_at',
        'expires_at',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getAuditName(): string
    {
        return $this->order_no;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Bekliyor',
            'paid' => 'Ödendi',
            'preparing' => 'Hazırlanıyor',
            'shipped' => 'Kargolandı',
            'delivered' => 'Teslim Edildi',
            'return_requested' => 'İade Talebi',
            'returned' => 'İade Edildi (Para İadesi)',
            'cancelled' => 'İptal',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'badge-amber',
            'paid', 'delivered' => 'badge-green',
            'preparing', 'shipped' => 'badge-indigo',
            'return_requested' => 'badge-amber',
            'returned' => 'badge-purple',
            'cancelled' => 'badge-red',
            default => 'badge-slate',
        };
    }

    /**
     * Kargo firmasının resmi takip URL'i (takip no ile birlikte).
     * Müşteri buna tıklayıp kargonun nerede olduğunu görür.
     */
    public function trackingUrl(): ?string
    {
        if (! $this->tracking_no || ! $this->carrier) {
            return null;
        }

        return match ($this->carrier) {
            'Yurtiçi Kargo' => 'https://www.yurticikargo.com/tr/Online-Servisler/Gonderi-Sorgula?code='.$this->tracking_no,
            'Aras Kargo' => 'https://www.araskargo.com.tr/tr/takip?sorgu='.$this->tracking_no,
            'MNG Kargo' => 'https://mngkargo.com.tr/takip?no='.$this->tracking_no,
            'PTT Kargo' => 'https://gonderitakip.ptt.gov.tr/sonuc-2/'.$this->tracking_no,
            'Sürat Kargo' => 'https://suratkargo.com.tr/tracking?trackingNumber='.$this->tracking_no,
            default => null, // Diğer — platform kendi linkini üretir
        };
    }
}
