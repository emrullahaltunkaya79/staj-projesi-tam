<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\RecordsActivity;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'role',
        'name',
        'email',
        'password',
        'verification_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Müşteri carisi — e-posta + şirket eşleşmesiyle (Google girişi bu cariyi otomatik açar). */
    public function contact()
    {
        return $this->hasOne(Contact::class, 'email', 'email')
            ->where('company_id', $this->company_id ?? null);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'Yönetici' : 'Kullanıcı';
    }
}
