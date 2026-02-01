<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDailyMetric extends Model
{
    protected $table = 'webhook_daily_metrics';

    protected $fillable = [
        'date',
        'webhook_id',
        'delivery_success_total',
        'delivery_failure_total',
        'retry_count',
        'total_latency_ms',
        'dlq_additions'
    ];
}
