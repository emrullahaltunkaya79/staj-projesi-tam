<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Şirket bazlı veri ayrımını sağlayan trait.
 *
 * - Giriş yapmış kullanıcının verileri dışındaki kayıtları otomatik gizler.
 * - Yeni kayıtlarda company_id'yi otomatik doldurur.
 *
 * Seeder / tinker gibi oturum olmadan çalışan ortamlarda etkisizdir;
 * bu durumda company_id açıkça verilmelidir.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check() && Auth::user()->company_id) {
                $builder->where($builder->getModel()->getTable().'.company_id', Auth::user()->company_id);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->company_id && ! $model->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
