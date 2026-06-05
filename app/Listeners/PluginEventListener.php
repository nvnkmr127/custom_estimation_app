<?php

namespace App\Listeners;

use App\Core\Events\DomainEvent;
use App\Models\PluginModule;
use App\Services\PluginService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PluginEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    protected $pluginService;

    public function __construct(PluginService $pluginService)
    {
        $this->pluginService = $pluginService;
    }

    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        $eventName = $event->getEventName();
        
        // Fetch active outbound plugin modules listening to this event name
        $modules = PluginModule::query()
            ->where('is_active', true)
            ->where('type', 'outbound')
            ->where('event_name', $eventName)
            ->whereHas('plugin', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        if ($modules->isEmpty()) {
            return;
        }

        Log::info("PluginEventListener: Dispatching " . $modules->count() . " active modules for event: {$eventName}");

        $payload = [
            'event' => $eventName,
            'entity_type' => $event->getEntityType(),
            'entity_id' => $event->getEntityId(),
            'triggered_by' => $event->getTriggeredBy(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Safely merge main event payload
        $eventPayload = $event->getPayload();
        if (is_array($eventPayload)) {
            $payload = array_merge($payload, $eventPayload);
        }

        // Fetch additional helper details for estimate events
        if ($event->getEntityType() === 'estimate' && isset($payload['id'])) {
            $estimate = \App\Models\Estimate::find($payload['id']);
            if ($estimate) {
                $payload['estimate_number'] = $estimate->estimate_number;
                $payload['total'] = $estimate->grand_total;
                $payload['status'] = $estimate->estimate_status;
                if ($estimate->client) {
                    $payload['client'] = [
                        'name' => $estimate->client->name,
                        'email' => $estimate->client->email,
                        'phone' => $estimate->client->phone ?? '',
                    ];
                }
            }
        }

        foreach ($modules as $module) {
            try {
                $this->pluginService->executeOutboundModule($module, $payload);
            } catch (\Exception $e) {
                Log::error("PluginEventListener error for module {$module->key} on event {$eventName}: " . $e->getMessage());
            }
        }
    }
}
