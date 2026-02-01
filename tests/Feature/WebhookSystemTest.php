<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

namespace Tests\Feature;

use App\Core\Events\DomainEvent;
use App\Jobs\ProcessInboundWebhook;
use App\Jobs\SendOutboundWebhook;
use App\Models\WebhookInboundEvent;
use App\Models\WebhookSubscription;
use App\Models\WebhookOutboundLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookSystemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_accepts_and_processes_inbound_webhooks()
    {
        Queue::fake();
        Config::set('services.perfex.webhook_secret', 'secret123');

        $payload = ['event_type' => 'proposal_accepted', 'proposal_id' => 123];
        $headers = ['X-Perfex-Token' => 'secret123'];

        // 1. Ingest
        $response = $this->postJson(route('webhooks.handle', ['provider' => 'perfex']), $payload, $headers);

        $response->assertStatus(202);

        $this->assertDatabaseHas('webhook_inbound_events', [
            'provider' => 'perfex',
            'provider_event_id' => '123',
            'status' => 'pending',
        ]);

        // 2. Queue Dispatch
        Queue::assertPushed(ProcessInboundWebhook::class);
    }

    /** @test */
    public function it_rejects_invalid_inbound_signature()
    {
        Config::set('services.perfex.webhook_secret', 'secret123');

        $payload = ['event_type' => 'proposal_accepted', 'proposal_id' => 123];
        $headers = ['X-Perfex-Token' => 'wrong_secret'];

        $response = $this->postJson(route('webhooks.handle', ['provider' => 'perfex']), $payload, $headers);

        $response->assertStatus(403);

        // Should still be logged but as failed
        $this->assertDatabaseHas('webhook_inbound_events', [
            'provider' => 'perfex',
            'status' => 'failed',
            'error' => 'Invalid signature',
        ]);
    }

    /** @test */
    public function it_dispatches_outbound_webhooks_to_subscribers()
    {
        Queue::fake();

        // Setup Config
        $config = \App\Models\WebhookConfig::create([
            'name' => 'Test Hook',
            'url' => 'https://example.com/webhook',
            'secret' => 'my-secret',
            'events' => ['estimate.*'],
            'status' => 'active',
        ]);

        // Trigger Event
        $event = new TestWebhookEvent('estimate.approved');

        // Manually register listener to verify logic
        Event::listen(\App\Core\Events\DomainEvent::class, [\App\Listeners\WebhookDispatchListener::class, 'handle']);

        // Dispatch
        event($event);

        // Assert Job Pushed
        Queue::assertPushed(SendOutboundWebhook::class, function ($job) use ($config, $event) {
            return $job->webhookConfig->id === $config->id
                && $job->webhookEvent->idempotency_key === $event->getEventId();
        });

        // Assert Event Stored
        $this->assertDatabaseHas('webhook_events', [
            'event_type' => 'estimate.approved',
            'idempotency_key' => $event->getEventId(),
        ]);
    }

    /** @test */
    public function it_delivers_outbound_webhook_and_logs_success()
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $config = \App\Models\WebhookConfig::create([
            'name' => 'Test Hook',
            'url' => 'https://example.com/webhook',
            'secret' => 'my-secret',
            'events' => ['*'],
        ]);

        $webhookEvent = \App\Models\WebhookEvent::create([
            'event_type' => 'test.event',
            'idempotency_key' => 'msg-123',
            'payload' => ['foo' => 'bar'], // Simplified for test
            'occurred_at' => now(),
        ]);

        $job = new SendOutboundWebhook($config, $webhookEvent);
        $job->handle();

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $config->id,
            'webhook_event_id' => $webhookEvent->id,
            'response_status' => 200,
            'status' => 'success',
            'attempt' => 1,
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Webhook-Signature');
        });
    }

    /** @test */
    public function it_handles_outbound_failure_and_retries()
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'server error'], 500),
        ]);

        $config = \App\Models\WebhookConfig::create([
            'name' => 'Test Hook',
            'url' => 'https://example.com/webhook',
            'events' => ['*'],
        ]);

        $webhookEvent = \App\Models\WebhookEvent::create([
            'event_type' => 'test.event',
            'idempotency_key' => 'msg-123',
            'payload' => ['foo' => 'bar'],
            'occurred_at' => now(),
        ]);

        $job = new SendOutboundWebhook($config, $webhookEvent);

        try {
            $job->handle();
        } catch (\Exception $e) {
            $this->assertEquals('Webhook delivery failed with status 500', $e->getMessage());
        }

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $config->id,
            'response_status' => 500,
            'status' => 'retrying',
            'attempt' => 1,
        ]);
    }
}

class TestWebhookEvent implements DomainEvent
{
    public function __construct(private string $name)
    {
    }
    public function getEventId(): string
    {
        return 'msg-123';
    }
    public function getEventName(): string
    {
        return $this->name;
    }
    public function getOccurredOn(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
    public function getPayload(): array
    {
        return ['foo' => 'bar', 'id' => 1];
    }
    public function getEntityType(): string
    {
        return 'test';
    }
    public function getEntityId(): int|string|null
    {
        return 1;
    }
    public function getTriggeredBy(): ?int
    {
        return 1;
    }
    public function getSource(): string
    {
        return 'system';
    }
    public function getPriority(): string
    {
        return 'normal';
    }
}
