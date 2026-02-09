<?php

namespace App\Livewire\Admin\Webhooks\Events;

use Livewire\Component;
use App\Models\WebhookEvent;

class Show extends Component
{
    public WebhookEvent $event;

    public ?string $selectedDeliveryId = null;

    public function mount(WebhookEvent $event)
    {
        $this->event = $event->load(['deliveries.webhook']);
    }

    public function selectDelivery(string $id)
    {
        $this->selectedDeliveryId = $id;
    }

    public function closeDeliveryDetails()
    {
        $this->selectedDeliveryId = null;
    }

    public function getSelectedDeliveryProperty()
    {
        return $this->event->deliveries->firstWhere('id', $this->selectedDeliveryId);
    }

    public function retryDelivery(string $deliveryId)
    {
        $delivery = \App\Models\WebhookDelivery::findOrFail($deliveryId);
        $webhookConfig = $delivery->webhook;
        $webhookEvent = $delivery->event;

        if (!$webhookConfig || !$webhookEvent) {
            $this->dispatch('notify', ['message' => 'Cannot retry: Config or Event missing', 'type' => 'error']);
            return;
        }

        \App\Jobs\WebhookDeliveryJob::dispatch($webhookConfig, $webhookEvent);

        $this->dispatch('notify', ['message' => 'Delivery retried successfully', 'type' => 'success']);
        $this->event->load('deliveries.webhook'); // Refresh
    }

    public function replayEvent()
    {
        $dispatcher = app(\App\Webhooks\WebhookEventDispatcher::class);
        $dispatcher->dispatchExisting($this->event);

        $this->dispatch('notify', ['message' => 'Event scheduled for replay to all active subscribers', 'type' => 'success']);
        $this->event->load('deliveries.webhook'); // Refresh
    }

    public function render()
    {
        return view('livewire.admin.webhooks.events.show', [
            'selectedDelivery' => $this->selectedDelivery,
        ]);
    }
}
