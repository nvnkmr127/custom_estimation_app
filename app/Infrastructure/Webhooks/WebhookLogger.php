<?php

namespace App\Infrastructure\Webhooks;

use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;

class WebhookLogger
{
    public function logAttempt(WebhookDelivery $delivery, string $status, array $context = [], ?WebhookEvent $event = null): void
    {
        // Use provided event or fallback to relationship (lazy load)
        $event = $event ?? $delivery->event;
        $payload = $event->payload ?? [];
        $meta = $payload['meta'] ?? [];

        $logData = [
            'type' => 'webhook_delivery',
            'trace_id' => $meta['trace_id'] ?? null,
            'event_id' => $delivery->webhook_event_id,
            'endpoint_id' => $delivery->webhook_id,
            'delivery_id' => $delivery->id,
            'status' => $status,
            'attempt' => $delivery->attempt,
            'latency_ms' => $context['duration_ms'] ?? null,
            'response_status' => $context['response_status'] ?? null,
            'retry_count' => $delivery->attempt - 1, // 1st attempt = 0 retries
            'timestamp' => now()->toIso8601String(),
        ];

        // Ensure machine-parseable JSON
        Log::channel('daily')->info('Webhook Delivery Log', $logData);
    }
}
