<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Models\WebhookDailyMetric;
use App\Jobs\WebhookDeliveryJob;
use App\Webhooks\WebhookMetricsCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class WebhookMetricsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_records_success_and_latency_metrics()
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $config = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create();

        $job = new WebhookDeliveryJob($config, $event);
        $job->handle();

        $this->assertDatabaseHas('webhook_daily_metrics', [
            'webhook_id' => $config->id,
            'date' => now()->toDateString(),
            'delivery_success_total' => 1,
            'delivery_failure_total' => 0,
        ]);

        $metric = WebhookDailyMetric::first();
        $this->assertGreaterThan(0, $metric->total_latency_ms);
    }

    /** @test */
    public function it_records_dlq_metrics()
    {
        Http::fake([
            '*' => Http::response('Server Error', 500),
        ]);

        $config = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create();

        // Simulate exhaustion
        $job = new WebhookDeliveryJob($config, $event);
        $job->tries = 1; // Try once, fail

        try {
            $job->handle();
        } catch (\Exception $e) {
        }

        $this->assertDatabaseHas('webhook_daily_metrics', [
            'webhook_id' => $config->id,
            'dlq_additions' => 1,
            'delivery_failure_total' => 1,
        ]);
    }

    /** @test */
    public function collector_calculates_aggregates()
    {
        $config = WebhookConfig::factory()->create();
        $collector = new WebhookMetricsCollector();

        // 2 Successes (100ms + 300ms) = 200ms avg
        $collector->increment($config->id, 'success', 100);
        $collector->increment($config->id, 'success', 300);
        $collector->increment($config->id, 'retry');

        $metrics = $collector->getMetrics($config->id);

        $this->assertEquals(2, $metrics[0]['success']);
        $this->assertEquals(1, $metrics[0]['retries']);
        $this->assertEquals(200, $metrics[0]['avg_latency']);
    }
}
