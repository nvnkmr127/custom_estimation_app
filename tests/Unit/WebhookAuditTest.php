<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\WebhookDeliveryJob;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_request_headers_and_payload()
    {
        Http::fake();

        $config = WebhookConfig::factory()->create([
            'headers' => ['X-Custom' => 'Foo'],
            'secret' => 'audit-secret',
        ]);

        $event = WebhookEvent::factory()->create([
            'payload' => ['foo' => 'bar'],
        ]);

        $job = new WebhookDeliveryJob($config, $event);
        $job->handle();

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $config->id,
            'webhook_event_id' => $event->id,
            'status' => 'success',
        ]);

        $delivery = \App\Models\WebhookDelivery::first();

        // Audit Check
        $this->assertNotNull($delivery->request_headers);
        $this->assertEquals('Foo', $delivery->request_headers['X-Custom']);
        $this->assertArrayHasKey('X-Webhook-Signature', $delivery->request_headers);

        // Check Payload via Event Relation
        $this->assertEquals(['foo' => 'bar'], $delivery->event->payload);

        // Check Response
        $this->assertEquals(200, $delivery->response_status);
    }
}
