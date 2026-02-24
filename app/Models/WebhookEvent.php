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
        // - if no deliveries: pending/skipped/inactive
        // - if all success: success
        // - if all failed: failed
        // - mixed: partial

        $total = $this->deliveries->count();

        if ($total === 0) {
            // Check if there are any potential subscribers
            $eventName = $this->event_type;
            $configs = \App\Models\WebhookConfig::all();

            $matched = $configs->filter(function ($config) use ($eventName) {
                if (empty($config->events))
                    return false;
                foreach ($config->events as $pattern) {
                    if (fnmatch($pattern, $eventName))
                        return true;
                }
                return false;
            });

            if ($matched->isEmpty()) {
                return 'no_subscribers';
            }

            if ($matched->where('status', 'active')->isEmpty()) {
                return 'inactive';
            }

            return 'pending';
        }

        $counts = $this->deliveries->groupBy('status')->map->count();
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
