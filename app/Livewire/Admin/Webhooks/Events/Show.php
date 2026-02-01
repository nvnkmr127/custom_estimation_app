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

    public function render()
    {
        return view('livewire.admin.webhooks.events.show', [
            'selectedDelivery' => $this->selectedDelivery,
        ]);
    }
}
