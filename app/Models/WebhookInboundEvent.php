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
        'error_message',
        'attempt_count',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'processed_at' => 'datetime',
    ];

    public function replay()
    {
        // Re-open this event for processing
        $this->update([
            'status' => 'pending',
            'error_message' => null,
            'error' => null,
            'attempt_count' => ($this->attempt_count ?? 0) + 1
        ]);

        // Logic to re-trigger processing
        \App\Jobs\ProcessInboundWebhook::dispatchSync($this);
    }
}
