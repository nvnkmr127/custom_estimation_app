<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Webhooks\WebhookReplayService;
use App\Webhooks\WebhookEventDispatcher;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Models\WebhookDelivery;
use App\Jobs\WebhookDeliveryJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class WebhookReplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_replays_event_to_all_subscribers()
    {
        Queue::fake();
        $this->seedConfig();

        $originalEvent = WebhookEvent::factory()->create([
            'event_type' => 'test.event'
        ]);

        $service = app(WebhookReplayService::class);
        $service->replayEvent($originalEvent);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($originalEvent) {
            return $job->webhookEvent->id === $originalEvent->id;
        });
    }

    /** @test */
    public function it_replays_specific_delivery()
    {
        Queue::fake();
        $config = $this->seedConfig();

        $originalEvent = WebhookEvent::factory()->create([
            'event_type' => 'test.event'
        ]);

        $delivery = WebhookDelivery::create([
            'webhook_id' => $config->id,
            'webhook_event_id' => $originalEvent->id,
            'status' => 'failed',
            'attempt' => 1
        ]);

        $service = app(WebhookReplayService::class);
        $service->replayDelivery($delivery);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($config, $originalEvent) {
            return $job->webhookConfig->id === $config->id
                && $job->webhookEvent->id === $originalEvent->id;
        });
    }

    /** @test */
    public function it_clones_event_when_payload_overridden()
    {
        Queue::fake();
        $this->seedConfig();

        $originalEvent = WebhookEvent::factory()->create([
            'event_type' => 'test.event',
            'payload' => ['normalized' => ['foo' => 'bar']]
        ]);

        $service = app(WebhookReplayService::class);
        $overriddenPayload = ['foo' => 'baz'];

        $dispatchCount = $service->replayEvent($originalEvent, $overriddenPayload);
        $newEvent = WebhookEvent::where('id', '!=', $originalEvent->id)->latest('id')->first();

        $this->assertGreaterThan(0, $dispatchCount);
        $this->assertNotNull($newEvent);
        $this->assertNotEquals($originalEvent->id, $newEvent->id);
        $this->assertEquals($overriddenPayload, $newEvent->payload['normalized']);
        $this->assertTrue($newEvent->payload['metadata']['is_replay']);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($newEvent) {
            return $job->webhookEvent->id === $newEvent->id;
        });
    }

    /** @test */
    public function it_prevents_infinite_replay_loops()
    {
        $service = app(WebhookReplayService::class);

        $event = WebhookEvent::factory()->create([
            'payload' => [
                'metadata' => ['replay_count' => 3]
            ]
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Max replay depth exceeded");

        $service->replayEvent($event, ['foo' => 'bar']); // Trigger clone logic which checks depth
    }

    protected function seedConfig()
    {
        return WebhookConfig::factory()->create([
            'url' => 'http://example.com',
            'events' => ['test.event'],
            'status' => 'active'
        ]);
    }
}
