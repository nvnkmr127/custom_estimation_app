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
        $service = app(\App\Webhooks\WebhookReplayService::class);
        $count = $service->replayEvent($this->event);

        if ($count > 0) {
            $this->dispatch('notify', ['message' => "Event scheduled for replay to {$count} active subscribers", 'type' => 'success']);
        } else {
            $this->dispatch('notify', ['message' => 'No active subscribers found for this event type.', 'type' => 'warning']);
        }

        $this->event->load('deliveries.webhook'); // Refresh
    }

    public function forceReplayEvent()
    {
        $service = app(\App\Webhooks\WebhookReplayService::class);
        $count = $service->replayEvent($this->event, null, true);

        if ($count > 0) {
            $this->dispatch('notify', ['message' => "Event FORCE-scheduled for replay to {$count} matched subscribers (including inactive)", 'type' => 'success']);
        } else {
            $this->dispatch('notify', ['message' => 'No matching subscribers found at all.', 'type' => 'error']);
        }

        $this->event->load('deliveries.webhook'); // Refresh
    }

    public function getMatchingSubscribersProperty()
    {
        return app(\App\Webhooks\WebhookEventDispatcher::class)->resolveSubscribers($this->event->event_type);
    }

    public function getPotentialSubscribersProperty()
    {
        // All webhooks that match pattern but might be inactive
        return \App\Models\WebhookConfig::all()
            ->filter(function ($config) {
                if (empty($config->events))
                    return false;
                foreach ($config->events as $pattern) {
                    if (fnmatch($pattern, $this->event->event_type))
                        return true;
                }
                return false;
            });
    }

    public function render()
    {
        return view('livewire.admin.webhooks.events.show', [
            'selectedDelivery' => $this->selectedDelivery,
            'matchingSubscribers' => $this->matchingSubscribers,
            'potentialSubscribers' => $this->potentialSubscribers,
        ]);
    }
}
