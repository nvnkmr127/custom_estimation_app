<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookInboundEvent extends Model
{
    protected $fillable = [
        'provider',
        'provider_event_id',
        'payload',
        'headers',
        'status',
        'error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'processed_at' => 'datetime',
    ];
}
