<?php

namespace App\Livewire\Admin\Webhooks\Dlq;

use App\Jobs\WebhookDeliveryJob;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookEvent;
use App\Models\WebhookDelivery;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $editingId = null;
    public $payloadJson = '';

    public function render()
    {
        $deadLetters = WebhookDeadLetter::with(['originalDelivery.webhook', 'originalDelivery.event'])
            ->latest('failed_at')
            ->paginate(15);

        return view('livewire.admin.webhooks.dlq.index', [
            'deadLetters' => $deadLetters
        ]);
    }

    public function replay($id)
    {
        $deadLetter = WebhookDeadLetter::findOrFail($id);

        // Mark as replayed
        $deadLetter->update(['replayed' => true]);

        // Re-dispatch logic
        // We act as if it's a new attempt attempt (or next attempt).
        // Best approach: Create a NEW delivery record linked to the same event, but potentially with modified payload if we supported editing.
        // For simple replay:

        $originalDelivery = $deadLetter->originalDelivery;
        $webhookConfig = $originalDelivery->webhook;
        $webhookEvent = $originalDelivery->event;

        if (!$webhookConfig || !$webhookEvent) {
            session()->flash('flash.banner', 'Cannot replay: Webhook or Event deleted.');
            session()->flash('flash.bannerStyle', 'danger');
            return;
        }

        // Dispatch Job
        // Note: The job will create a NEW delivery record.
        // If we want to capture that this was a replay from DLQ, we might need extra context, but standard flow works.
        WebhookDeliveryJob::dispatch($webhookConfig, $webhookEvent);

        session()->flash('flash.banner', 'Webhook re-queued successfully.');
    }

    public function editAndReplay($id)
    {
        $deadLetter = WebhookDeadLetter::findOrFail($id);
        $this->editingId = $id;
        $this->payloadJson = json_encode($deadLetter->snapshot_payload, JSON_PRETTY_PRINT);
        $this->dispatch('open-modal', 'edit-payload');
    }

    public function confirmReplay()
    {
        $this->validate([
            'payloadJson' => 'required|json',
        ]);

        $deadLetter = WebhookDeadLetter::findOrFail($this->editingId);
        $newPayload = json_decode($this->payloadJson, true);

        // To support modified payload, we can't just dispatch the existing Event model because it has the OLD payload.
        // Options:
        // 1. Update the Event model (Side effect: changes historical data).
        // 2. Create a NEW Event model (cleanest for audit trail).
        // 3. Pass payload override to Job (Job needs update).

        // Let's go with creating a NEW Event to preserve history.
        // Clone the event
        $originalEvent = $deadLetter->originalDelivery->event;

        $newEvent = $originalEvent->replicate();
        $newEvent->id = (string) \Illuminate\Support\Str::uuid(); // New ID
        $newEvent->idempotency_key = (string) \Illuminate\Support\Str::uuid(); // New Idempotency Key
        $newEvent->payload = $newPayload;
        $newEvent->occurred_at = now(); // Update timestamp to now? Or keep original? Ideally now since it's a new "event" effectively.
        $newEvent->save();

        $deadLetter->update(['replayed' => true]);

        $webhookConfig = $deadLetter->originalDelivery->webhook;
        WebhookDeliveryJob::dispatch($webhookConfig, $newEvent);

        $this->reset(['editingId', 'payloadJson']);
        $this->dispatch('close-modal', 'edit-payload');
        session()->flash('flash.banner', 'Replayed with new payload. New Event ID created.');
    }

    public function delete($id)
    {
        WebhookDeadLetter::destroy($id);
        session()->flash('flash.banner', 'Dead letter removed.');
    }
}
