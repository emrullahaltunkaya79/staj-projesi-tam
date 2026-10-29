<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class ContactPrice extends Model
{
    use BelongsToCompany, RecordsActivity;

    protected $fillable = [
        'company_id',
        'contact_id',
        'product_id',
        'price',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Belirli cari-ürün çifti için özel fiyatı döndürür (yoksa null).
     */
    public static function priceFor(int $contactId, int $productId): ?float
    {
        $row = static::where('contact_id', $contactId)->where('product_id', $productId)->first();

        return $row ? (float) $row->price : null;
    }
}
