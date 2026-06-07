<?php

namespace Tests\Feature;

use App\Core\Events\DomainEvent;
use App\Jobs\ProcessInboundWebhook;
use App\Jobs\WebhookDeliveryJob;
use App\Jobs\SendOutboundWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_and_processes_inbound_webhooks()
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

        Queue::assertPushed(ProcessInboundWebhook::class);
    }

    public function test_it_rejects_invalid_inbound_signature()
    {
        Config::set('services.perfex.webhook_secret', 'secret123');

        $payload = ['event_type' => 'proposal_accepted', 'proposal_id' => 123];
        $headers = ['X-Perfex-Token' => 'wrong_secret'];

        $response = $this->postJson(route('webhooks.handle', ['provider' => 'perfex']), $payload, $headers);

        $response->assertStatus(403);

        $this->assertDatabaseHas('webhook_inbound_events', [
            'provider' => 'perfex',
            'status' => 'failed',
            'error' => 'Invalid signature',
        ]);
    }

    public function test_it_dispatches_outbound_webhooks_to_subscribers()
    {
        Queue::fake();

        $config = \App\Models\WebhookConfig::create([
            'name' => 'Test Hook',
            'url' => 'https://example.com/webhook',
            'secret' => 'my-secret',
            'events' => ['estimate.*'],
            'status' => 'active',
        ]);

        $event = new TestWebhookEvent('estimate.approved');

        Event::listen(\App\Core\Events\DomainEvent::class, [\App\Listeners\WebhookDispatchListener::class, 'handle']);

        event($event);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($config, $event) {
            return $job->webhookConfig->id === $config->id
                && $job->webhookEvent->idempotency_key === $event->getEventId();
        });

        $this->assertDatabaseHas('webhook_events', [
            'event_type' => 'estimate.approved',
            'idempotency_key' => $event->getEventId(),
        ]);
    }

    public function test_it_delivers_outbound_webhook_and_logs_success()
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
            'payload' => ['foo' => 'bar'],
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

    public function test_it_handles_outbound_failure_and_retries()
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

    public function test_it_dispatches_deleted_outbound_webhook_to_subscribers()
    {
        Queue::fake();

        $config = \App\Models\WebhookConfig::create([
            'name' => 'Test Hook',
            'url' => 'https://example.com/webhook',
            'secret' => 'my-secret',
            'events' => ['estimate.*'],
            'status' => 'active',
        ]);

        $user = \App\Models\User::factory()->create(['role' => 'estimator_admin']);
        $client = \App\Models\Client::factory()->create();
        $estimate = \App\Models\Estimate::factory()->create([
            'client_id' => $client->id,
            'is_current_version' => true,
            'created_by' => $user->id,
        ]);

        Event::listen(\App\Core\Events\DomainEvent::class, [\App\Listeners\WebhookDispatchListener::class, 'handle']);

        // Delete estimate via service which dispatches EstimateDeleted event
        $this->actingAs($user);
        app(\App\Services\EstimateService::class)->deleteEstimate($estimate);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($config, $estimate) {
            return $job->webhookConfig->id === $config->id
                && $job->webhookEvent->event_type === 'estimate.deleted'
                && $job->webhookEvent->payload['data']['id'] === $estimate->id;
        });

        $this->assertDatabaseHas('webhook_events', [
            'event_type' => 'estimate.deleted',
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
