<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\WebhookDeliveryJob;
use App\Webhooks\WebhookDLQProcessor;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookDeadLetter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookDLQTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_dlq_entry_on_permanent_failure()
    {
        Http::fake([
            '*' => Http::response('Not Found', 404) // Permanent Fail
        ]);

        $config = WebhookConfig::factory()->create(['url' => 'https://example.com/404']);
        $event = WebhookEvent::factory()->create();

        $job = new WebhookDeliveryJob($config, $event);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Should fail but Job suppresses re-throw on 4xx if correctly implemented
            // Wait, I used $this->fail($e) which marks job as failed in Laravel, 
            // but does NOT throw exception up the stack in Sync driver usually?
            // Actually in Sync driver, fail() marks it but exception might bubble if not caught?
            // job->handle() calls handleFailure -> fail().
        }

        $dlq = WebhookDeadLetter::with('originalDelivery')->first();
        $this->assertNotNull($dlq);
        $this->assertNotNull($dlq->originalDelivery);
        $this->assertEquals($config->id, $dlq->originalDelivery->webhook_id);
        $this->assertEquals($event->id, $dlq->originalDelivery->webhook_event_id);
        $this->assertStringContainsString('HTTP 404', $dlq->final_error_message);

        // Assert Delivery Failed
        $this->assertDatabaseHas('webhook_deliveries', [
            'status' => 'failed',
            'response_status' => 404,
        ]);
    }

    /** @test */
    public function it_requeues_dead_letter()
    {
        Queue::fake();

        $config = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create();
        $delivery = WebhookDelivery::create([
            'webhook_id' => $config->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed',
            'attempt' => 5
        ]);

        $dlq = WebhookDeadLetter::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'original_delivery_id' => $delivery->id,
            'final_error_message' => 'failed: Max retries',
            'snapshot_payload' => $event->payload,
            'replayed' => false,
        ]);

        $processor = app(WebhookDLQProcessor::class);
        $result = $processor->requeue($dlq);

        $this->assertTrue($result);

        // Assert DLQ deleted
        $this->assertDatabaseMissing('webhook_dead_letters', ['id' => $dlq->id]);

        // Assert New Job Pushed (via Replay logic)
        Queue::assertPushed(WebhookDeliveryJob::class);
    }
}
