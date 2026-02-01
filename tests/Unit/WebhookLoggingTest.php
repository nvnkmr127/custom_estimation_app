<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Jobs\WebhookDeliveryJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class WebhookLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_structured_data_on_delivery()
    {
        Log::shouldReceive('channel')->with('daily')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Webhook Delivery Log', \Mockery::on(function ($data) {
            dump($data);
            return $data['trace_id'] === 'trace-123'
                && $data['event_id'] !== null
                && $data['status'] === 'success'
                && isset($data['latency_ms']);
        }));

        Http::fake();

        $config = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create([
            'payload' => [
                'meta' => ['trace_id' => 'trace-123'],
                'data' => []
            ]
        ]);

        $job = new WebhookDeliveryJob($config, $event);
        $job->handle();
    }
}
