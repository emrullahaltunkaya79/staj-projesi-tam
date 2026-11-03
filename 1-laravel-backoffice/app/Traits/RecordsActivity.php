<?php

namespace App\Traits;

use App\Models\ActivityLog;

/**
 * Denetim izi: kaydın oluşturulma/güncellenme/silinme olaylarını
 * activity_logs tablosuna yazar. ActivityLog kendi kendini loglamaz.
 */
trait RecordsActivity
{
    public static function bootRecordsActivity(): void
    {
        static::created(fn ($model) => ActivityLog::record($model, 'created'));

        static::updated(function ($model) {
            // Yalnızca gerçekten değişen alanları kaydet (iç belge alanlarını atla)
            $dirty = collect($model->getDirty())
                ->except(['updated_at', 'remember_token', 'invoice_no', 'quote_no', 'password', 'logo_path']);

            ActivityLog::record(
                $model,
                'updated',
                $dirty->isNotEmpty() ? $dirty->toArray() : null
            );
        });

        static::deleted(fn ($model) => ActivityLog::record($model, 'deleted'));

        // SoftDeletes ile geri yükleme olayı (yalnızca soft delete kullanan modellerde var)
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(fn ($model) => ActivityLog::record($model, 'restored'));
        }
    }

    /**
     * Denetim kayıtlarında görünecek kısa ad (alt sınıflar ezebilir).
     */
    public function getAuditName(): string
    {
        return $this->name ?? $this->title ?? ('#'.$this->getKey());
    }
}
