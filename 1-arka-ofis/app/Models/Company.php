<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\RecordsActivity;

class Company extends Model
{
    use HasFactory, RecordsActivity;

    protected $fillable = ['name', 'logo_path', 'tax_number', 'phone', 'address'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Logoyu base64 data-URI olarak döndürür — hem tarayıcıda hem DomPDF'te
     * (harici dosya erişimi kapalıyken) sorunsuz görüntülenir.
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path || ! Storage::exists($this->logo_path)) {
            return null;
        }

        $mime = match (pathinfo($this->logo_path, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode(Storage::get($this->logo_path));
    }
}
