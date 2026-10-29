<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Ayar oku — DB'de yoksa config/env fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $cacheKey = "setting:{$key}";

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $row = static::where('key', $key)->first();

            return $row?->value ?? $default;
        });
    }

    /**
     * Ayar yaz — cache temizlenir.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("setting:{$key}");
    }

    /**
     * Casper alıcı adresi: önce DB, sonra env fallback.
     */
    public static function casperAddress(): ?string
    {
        return static::get('casper_receiving_address', config('services.casper.receiving_address'));
    }

    public static function casperNetwork(): string
    {
        return static::get('casper_network', config('services.casper.network', 'testnet'));
    }

    public static function casperRate(): string
    {
        return static::get('casper_rate', config('services.casper.rate', '0.1'));
    }

    public static function webhookUrl(): ?string
    {
        return static::get('ecommerce_webhook_url', config('services.ecommerce.webhook_url'));
    }

    public static function webhookSecret(): string
    {
        return static::get('ecommerce_webhook_secret', config('services.ecommerce.webhook_secret', 'change-me'));
    }
}
