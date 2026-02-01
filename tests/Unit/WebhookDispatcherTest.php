<?php

namespace Tests\Unit;

use App\Webhooks\WebhookEventDispatcher;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Jobs\SendOutboundWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class WebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_webhooks_to_subscribers()
    {
        Queue::fake();

        // 1. Setup Subscriber
        $config = WebhookConfig::create([
            'name' => 'Test Sub',
            'url' => 'https://example.com/hook',
            'events' => ['test.*'],
            'status' => 'active'
        ]);

        // 2. Mock Domain Event
        $event = Mockery::mock(\App\Core\Events\DomainEvent::class);
        $event->shouldReceive('getEventName')->andReturn('test.created');
        $event->shouldReceive('getEventId')->andReturn('uuid-123');
        $event->shouldReceive('getOccurredOn')->andReturn(new \DateTimeImmutable());
        $event->shouldReceive('getPayload')->andReturn(['foo' => 'bar']);
        $event->shouldReceive('getEntityType')->andReturn('test');
        $event->shouldReceive('getEntityId')->andReturn(1);

        // 3. Dispatch
        $dispatcher = app(WebhookEventDispatcher::class);
        $dispatcher->dispatch($event);

        // 4. Assert Job Pushed
        Queue::assertPushed(\App\Jobs\WebhookDeliveryJob::class, function ($job) use ($config) {
            return $job->webhookConfig->id === $config->id
                && $job->webhookEvent->idempotency_key === 'uuid-123';
        });

        // 5. Assert Event Persisted
        $this->assertDatabaseHas('webhook_events', [
            'idempotency_key' => 'uuid-123',
            'event_type' => 'test.created',
        ]);
    }

    /** @test */
    public function it_is_idempotent()
    {
        Queue::fake();

        // 1. Setup
        WebhookConfig::create([
            'name' => 'Test Sub',
            'url' => 'https://example.com',
            'events' => ['test.*'],
            'status' => 'active'
        ]);

        $event = Mockery::mock(\App\Core\Events\DomainEvent::class);
        $event->shouldReceive('getEventName')->andReturn('test.created');
        $event->shouldReceive('getEventId')->andReturn('uuid-unique');
        $event->shouldReceive('getOccurredOn')->andReturn(new \DateTimeImmutable());
        $event->shouldReceive('getPayload')->andReturn([]);
        $event->shouldReceive('getEntityType')->andReturn('test');
        $event->shouldReceive('getEntityId')->andReturn(1);

        $dispatcher = app(WebhookEventDispatcher::class);

        // 2. Dispatch First Time
        $dispatcher->dispatch($event);
        Queue::assertPushed(\App\Jobs\WebhookDeliveryJob::class, 1);

        // 3. Dispatch Second Time (Same ID)
        $dispatcher->dispatch($event);

        // Should NOT push again
        Queue::assertPushed(\App\Jobs\WebhookDeliveryJob::class, 1);
    }
}
