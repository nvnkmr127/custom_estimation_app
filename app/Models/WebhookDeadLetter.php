<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookDeadLetter extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'webhook_dead_letters';
    public $timestamps = false;

    protected $fillable = [
        'original_delivery_id',
        'final_error_message',
        'snapshot_payload',
        'replayed'
    ];

    protected $casts = [
        'snapshot_payload' => 'array',
        'replayed' => 'boolean',
        'failed_at' => 'datetime'
    ];

    public function webhook(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            WebhookConfig::class,
            WebhookDelivery::class,
            'id', // Foreign key on deliveries table... wait.
            'id', // Foreign key on webhooks table... no.
            'original_delivery_id', // Local key on dead_letters table
            'webhook_id' // Local key on deliveries table
        );
    }

    // Easier: Accessor or delegating via delivery relation
    public function getWebhookAttribute()
    {
        return $this->originalDelivery->webhook;
    }

    public function getEventAttribute()
    {
        return $this->originalDelivery->event;
    }

    public function originalDelivery(): BelongsTo
    {
        return $this->belongsTo(WebhookDelivery::class, 'original_delivery_id');
    }
}
