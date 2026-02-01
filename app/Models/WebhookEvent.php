<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookEvent extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'webhook_events';

    protected $fillable = [
        'event_type',
        'idempotency_key',
        'payload',
        'occurred_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime'
    ];
    public function deliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_event_id');
    }

    public function getDeliveryStatusAttribute(): string
    {
        // Aggregate status:
        // - if no deliveries: pending
        // - if all success: success
        // - if all failed: failed
        // - mixed: partial

        $counts = $this->deliveries->groupBy('status')->map->count();
        $total = $this->deliveries->count();

        if ($total === 0)
            return 'pending';

        $success = $counts->get('success', 0);
        $failed = $counts->get('failed', 0);
        $retrying = $counts->get('retrying', 0) + $counts->get('processing', 0) + $counts->get('pending', 0); // Active

        if ($success === $total)
            return 'success';
        if ($failed === $total)
            return 'failed';
        if ($success > 0 && ($failed > 0 || $retrying > 0))
            return 'partial';

        return 'processing';
    }
}
