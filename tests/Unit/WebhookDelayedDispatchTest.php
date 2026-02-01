<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Webhooks\WebhookEventDispatcher;
use App\Models\WebhookConfig;
use App\Jobs\WebhookDeliveryJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;

class WebhookDelayedDispatchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_job_with_delay()
    {
        Queue::fake();

        // 1. Setup Config with Delay
        $config = WebhookConfig::factory()->create([
            'events' => ['delay.test'],
            'delay' => 600, // 10 minutes
        ]);

        // 2. Mock Event
        $event = Mockery::mock(\App\Core\Events\DomainEvent::class);
        $event->shouldReceive('getEventName')->andReturn('delay.test');
        $event->shouldReceive('getEventId')->andReturn('uuid-delay');
        $event->shouldReceive('getOccurredOn')->andReturn(new \DateTimeImmutable());
        $event->shouldReceive('getPayload')->andReturn([]);
        $event->shouldReceive('getEntityType')->andReturn('test');
        $event->shouldReceive('getEntityId')->andReturn(1);

        // 3. Dispatch
        $dispatcher = app(WebhookEventDispatcher::class);
        $dispatcher->dispatch($event);

        // 4. Assert Job Pushed with Delay
        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($config) {
            return $job->webhookConfig->id === $config->id
                && $job->delay == 600;
        });
    }

    /** @test */
    public function it_dispatches_job_without_delay_by_default()
    {
        Queue::fake();

        $config = WebhookConfig::factory()->create([
            'events' => ['no.delay'],
            'delay' => 0,
        ]);

        $event = Mockery::mock(\App\Core\Events\DomainEvent::class);
        $event->shouldReceive('getEventName')->andReturn('no.delay');
        $event->shouldReceive('getEventId')->andReturn('uuid-no-delay');
        $event->shouldReceive('getOccurredOn')->andReturn(new \DateTimeImmutable());
        $event->shouldReceive('getPayload')->andReturn([]);
        $event->shouldReceive('getEntityType')->andReturn('test');
        $event->shouldReceive('getEntityId')->andReturn(2);

        $dispatcher = app(WebhookEventDispatcher::class);
        $dispatcher->dispatch($event);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) {
            return is_null($job->delay) || $job->delay === 0;
        });
    }
}
