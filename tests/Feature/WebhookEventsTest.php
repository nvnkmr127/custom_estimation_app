<?php

namespace Tests\Feature;

use App\Livewire\Admin\Webhooks\Events\Index;
use App\Livewire\Admin\Webhooks\Events\Show;
use App\Models\WebhookEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebhookEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_events_index()
    {
        WebhookEvent::factory()->create(['event_type' => 'estimate.created']);

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('estimate.created');
    }

    /** @test */
    public function it_filters_events_by_type()
    {
        $estimateEvent = WebhookEvent::factory()->create(['event_type' => 'estimate.created']);
        $clientEvent = WebhookEvent::factory()->create(['event_type' => 'client.created']);

        Livewire::test(Index::class)
            ->set('type', 'estimate.created')
            ->assertSee($estimateEvent->id)
            ->assertDontSee($clientEvent->id);
    }

    /** @test */
    public function it_renders_event_details_and_timeline()
    {
        $event = WebhookEvent::factory()->create();
        $webhook = WebhookConfig::factory()->create(['name' => 'Test Hook']);

        WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'success',
            'attempt' => 1,
            'duration_ms' => 100,
            'response_status' => 200
        ]);

        Livewire::test(Show::class, ['event' => $event])
            ->assertOk()
            ->assertSee('Test Hook')
            ->assertSee('Attempt #1')
            ->assertSee('HTTP 200');
    }

    /** @test */
    public function it_shows_delivery_details_on_selection()
    {
        $event = WebhookEvent::factory()->create();
        $webhook = WebhookConfig::factory()->create();

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed',
            'attempt' => 1,
            'duration_ms' => 500,
            'response_status' => 500,
            'error_message' => 'Server Error',
            'request_headers' => ['X-Signature' => 'test-sig'],
            'response_body' => ['error' => 'Internal Error']
        ]);

        Livewire::test(Show::class, ['event' => $event])
            ->call('selectDelivery', $delivery->id)
            ->assertSet('selectedDeliveryId', $delivery->id)
            ->assertSee('Server Error')
            ->assertSee('test-sig')
            ->assertSee('Internal Error');
    }
}
