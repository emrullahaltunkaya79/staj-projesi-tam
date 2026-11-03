<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'changes',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Model olaylarından tek noktadan denetim kaydı oluşturur.
     */
    public static function record(object $model, string $action, ?array $changes = null): self
    {
        $user = auth()->user();
        $name = method_exists($model, 'getAuditName') ? $model->getAuditName() : ('#'.$model->getKey());

        // Şirket: önce oturumdaki kullanıcı, yoksa kaydın company_id özniteliği;
        // Company'nin kendi kaydı için şirketin kendisi kullanılır
        $companyId = $user?->company_id
            ?? (isset($model->company_id) ? (int) $model->company_id : null)
            ?? ($model instanceof Company ? $model->getKey() : null);

        return static::create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'description' => sprintf('%s kaydı %s: %s', class_basename($model), self::labelFor($action), $name),
            'changes' => $changes,
            'ip' => request()?->ip(),
        ]);
    }

    public function actionLabel(): string
    {
        return self::labelFor($this->action);
    }

    private static function labelFor(string $action): string
    {
        return match ($action) {
            'created' => 'oluşturuldu',
            'updated' => 'güncellendi',
            'deleted' => 'silindi',
            'restored' => 'geri yüklendi',
            default => $action,
        };
    }
}
