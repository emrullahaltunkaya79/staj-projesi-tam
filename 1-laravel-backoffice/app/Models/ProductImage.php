<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'path',
        'is_primary',
        'sort',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Görseli base64 data-URI olarak döndürür (thumb/API/payments için).
     */
    public function dataUri(): string
    {
        $mime = match (pathinfo($this->path, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode(Storage::get($this->path));
    }
}
