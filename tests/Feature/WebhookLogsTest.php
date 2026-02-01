<?php

namespace Tests\Feature;

use App\Livewire\Admin\Webhooks\Logs\Index;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use App\Models\WebhookConfig;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookLogsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_logs_and_expands_details()
    {
        $webhook = WebhookConfig::factory()->create(['name' => 'Logger Test']);
        $event = WebhookEvent::factory()->create(['payload' => ['meta' => ['trace_id' => 'TRC-123']]]);

        $log = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed',
            'error_message' => 'Connection Refused',
            'response_status' => 500,
            'request_headers' => ['Authorization' => 'Bearer token'],
            'created_at' => now()->subMinutes(5)
        ]);

        Livewire::test(Index::class)
            ->call('toggleRow', $log->id)
            ->assertSee('Connection Refused') // Visible after expansion
            ->assertSee('TRC-123') // Trace ID visible in details
            ->assertSee('Bearer token'); // Headers visible
    }

    /** @test */
    public function it_filters_logs()
    {
        $webhook = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create();

        WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'success',
            'created_at' => now()
        ]);

        WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed',
            'created_at' => now()
        ]);

        Livewire::test(Index::class)
            ->set('status', 'success')
            ->assertSeeHtml('bg-green-100') // Success badge
            ->assertDontSeeHtml('bg-red-100'); // Failed badge
    }
}
