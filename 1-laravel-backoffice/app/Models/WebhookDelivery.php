<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'event',
        'url',
        'payload',
        'response_code',
        'success',
        'attempts',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'success' => 'boolean',
            'delivered_at' => 'datetime',
        ];
    }
}
