<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WebhookDelivery extends Model
{
    use HasUuids;

    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'webhook_id',
        'webhook_event_id',
        'status',
        'attempt',
        'next_retry_at',
        'request_headers',
        'response_status',
        'response_body',
        'response_headers',
        'duration_ms',
        'error_message'
    ];

    protected $casts = [
        'next_retry_at' => 'datetime',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'response_status' => 'integer',
        'duration_ms' => 'integer',
        'attempt' => 'integer'
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(WebhookConfig::class, 'webhook_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }

    public function deadLetter(): HasOne
    {
        return $this->hasOne(WebhookDeadLetter::class, 'original_delivery_id');
    }
}
