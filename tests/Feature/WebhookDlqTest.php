<?php

namespace Tests\Feature;

use App\Livewire\Admin\Webhooks\Dlq\Index;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use App\Models\WebhookConfig;
use App\Jobs\WebhookDeliveryJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class WebhookDlqTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_dead_letters()
    {
        $webhook = WebhookConfig::factory()->create(['name' => 'Failed Hook']);
        $event = WebhookEvent::factory()->create();
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed'
        ]);

        WebhookDeadLetter::create([
            'original_delivery_id' => $delivery->id,
            'final_error_message' => 'Something went wrong',
            'snapshot_payload' => ['foo' => 'bar']
        ]);

        Livewire::test(Index::class)
            ->assertSee('Failed Hook')
            ->assertSee('Something went wrong');
    }

    /** @test */
    public function it_can_replay_dead_letter()
    {
        Queue::fake();

        $webhook = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create();
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed'
        ]);

        $dl = WebhookDeadLetter::create([
            'original_delivery_id' => $delivery->id,
            'final_error_message' => 'Error',
            'snapshot_payload' => ['foo' => 'bar']
        ]);

        Livewire::test(Index::class)
            ->call('replay', $dl->id);

        $this->assertTrue($dl->fresh()->replayed);
        Queue::assertPushed(WebhookDeliveryJob::class);
    }

    /** @test */
    public function it_can_edit_and_replay_dead_letter()
    {
        Queue::fake();

        $webhook = WebhookConfig::factory()->create();
        $event = WebhookEvent::factory()->create(['payload' => ['foo' => 'bar']]);
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'status' => 'failed'
        ]);

        $dl = WebhookDeadLetter::create([
            'original_delivery_id' => $delivery->id,
            'final_error_message' => 'Error',
            'snapshot_payload' => ['foo' => 'bar']
        ]);

        $newPayload = json_encode(['foo' => 'baz']);

        Livewire::test(Index::class)
            ->call('editAndReplay', $dl->id)
            ->set('payloadJson', $newPayload)
            ->call('confirmReplay');

        $this->assertTrue($dl->fresh()->replayed);

        // Verify new event created
        $this->assertDatabaseHas('webhook_events', [
            'payload' => $newPayload,
            'event_type' => $event->event_type
        ]);

        Queue::assertPushed(WebhookDeliveryJob::class, function ($job) use ($newPayload) {
            return json_encode($job->webhookEvent->payload) === $newPayload;
        });
    }
}
