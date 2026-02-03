<?php

namespace App\Infrastructure\Webhooks;

use App\Models\WebhookDailyMetric;
use Illuminate\Support\Facades\DB;

class WebhookMetricsCollector
{
    public function increment(string $webhookId, string $type, int $latencyMs = 0): void
    {
        $date = now()->toDateString();

        // Atomic upsert
        WebhookDailyMetric::updateOrCreate(
            ['date' => $date, 'webhook_id' => $webhookId],
            [] // No changes on simple find
        );

        // We use query builder increment to ensure atomicity under high concurrency
        $query = WebhookDailyMetric::where('date', $date)
            ->where('webhook_id', $webhookId);

        match ($type) {
            'success' => $query->increment('delivery_success_total'),
            'failure' => $query->increment('delivery_failure_total'),
            'retry' => $query->increment('retry_count'),
            'dlq' => $query->increment('dlq_additions'),
            default => null,
        };

        if ($latencyMs > 0) {
            $query->increment('total_latency_ms', $latencyMs);
        }
    }

    public function getMetrics(string $webhookId, int $days = 7): array
    {
        return WebhookDailyMetric::where('webhook_id', $webhookId)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get()
            ->map(function ($metric) {
                $totalReqs = $metric->delivery_success_total + $metric->delivery_failure_total;
                $avgLatency = $totalReqs > 0 ? round($metric->total_latency_ms / $totalReqs, 2) : 0;

                return [
                    'date' => $metric->date,
                    'success' => $metric->delivery_success_total,
                    'failure' => $metric->delivery_failure_total,
                    'retries' => $metric->retry_count,
                    'avg_latency' => $avgLatency,
                    'dlq_growth' => $metric->dlq_additions
                ];
            })->toArray();
    }
}
