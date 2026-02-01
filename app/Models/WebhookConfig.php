<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookConfig extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $table = 'webhooks';

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(WebhookDelivery::class, 'webhook_id')->latestOfMany();
    }

    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(WebhookDailyMetric::class, 'webhook_id');
    }

    public function getSuccessRateAttribute(): float
    {
        // Simple aggregate of all time (or last 30 days if we filter relation)
        // Optimization: Eager load 'dailyMetrics' in controller
        $totalSuccess = $this->dailyMetrics->sum('delivery_success_total');
        $totalFail = $this->dailyMetrics->sum('delivery_failure_total');

        $total = $totalSuccess + $totalFail;

        return $total > 0 ? round(($totalSuccess / $total) * 100, 1) : 0.0;
    }

    protected $fillable = [
        'name',
        'url',
        'http_method',
        'secret',
        'headers',
        'events',
        'status',
        'concurrency_limit',
        'rate_limit',
        'delay',
        'payload_mapping',
        'validation_schema'
    ];

    protected $casts = [
        'headers' => 'array',
        'events' => 'array',
        'concurrency_limit' => 'integer',
        'delay' => 'integer',
        'payload_mapping' => 'array',
        'validation_schema' => 'array'
    ];
}
