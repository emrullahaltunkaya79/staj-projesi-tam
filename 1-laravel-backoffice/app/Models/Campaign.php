<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Paket kampanya: "3 al 2 öde" (x_al_y_ode) veya "Eşik üstü seçili ürün yarım fiyat" (esik_yarim). */
class Campaign extends Model
{
    protected $fillable = [
        'company_id', 'ad', 'tur', 'urun_id',
        'al_adet', 'ode_adet', 'esik', 'aktif',
    ];

    protected function casts(): array
    {
        return [
            'al_adet' => 'integer',
            'ode_adet' => 'integer',
            'esik' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function urun()
    {
        return $this->belongsTo(Product::class, 'urun_id');
    }
}
