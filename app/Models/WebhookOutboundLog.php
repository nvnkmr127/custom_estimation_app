<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookOutboundLog extends Model
{
    protected $fillable = [
        'subscription_id',
        'event_name',
        'webhook_url',
        'payload',
        'request_headers',
        'response_status',
        'response_body',
        'latency_ms',
        'attempt',
        'status',
        'error_message',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'request_headers' => 'array',
        'next_retry_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
