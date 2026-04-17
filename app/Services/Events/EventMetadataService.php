<?php

namespace App\Services\Events;

use App\Webhooks\WebhookEventRegistry;

class EventMetadataService
{
    protected $registry;

    public function __construct(WebhookEventRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Get all supported events and their available variables.
     * Synchronized with the Webhook System.
     */
    public function getSupportedEvents(): array
    {
        $grouped = $this->registry->getGroupedEvents();
        $common = $this->getCommonVariables();
        $flatEvents = [];

        foreach ($grouped as $groupName => $events) {
            foreach ($events as $event) {
                $variables = $this->extractVariables($event['samplePayload'] ?? []);
                
                $flatEvents[$event['name']] = [
                    'name' => $event['name'],
                    'label' => $this->humanize($event['name']),
                    'group' => $groupName,
                    'description' => $event['description'],
                    'variables' => array_merge($common, $variables)
                ];
            }
        }

        return $flatEvents;
    }

    /**
     * Convert JSON payload into flattened dot-notation variables for the UI.
     */
    protected function extractVariables(array $payload, string $prefix = ''): array
    {
        $variables = [];
        foreach ($payload as $key => $value) {
            $fullKey = $prefix ? "{$prefix}_{$key}" : $key;
            
            if (is_array($value) && !empty($value) && !array_is_list($value)) {
                $variables = array_merge($variables, $this->extractVariables($value, $fullKey));
            } else {
                $variables[$fullKey] = $this->getVariableDescription($fullKey, $value);
            }
        }
        return $variables;
    }

    protected function getVariableDescription(string $key, $value): string
    {
        // Guess description based on key name
        if (str_contains($key, 'email')) return "Email address";
        if (str_contains($key, 'number')) return "Unique ID/Number";
        if (str_contains($key, 'total')) return "Financial total";
        if (str_contains($key, 'url') || str_contains($key, 'link')) return "Clickable link";
        if (str_contains($key, 'name')) return "User or Client name";
        
        return "Data field: " . (is_string($value) ? $value : gettype($value));
    }

    protected function humanize(string $name): string
    {
        return ucwords(str_replace(['.', '_'], ' ', $name));
    }

    public function getCommonVariables(): array
    {
        return [
            'metadata_event_id' => 'The unique ID for this specific event (Webhook ID)',
            'metadata_occurred_at' => 'Timestamp when the event happened',
            'metadata_source' => 'Origin of the event (web/api)',
            'company_name' => 'Your organization name from settings',
        ];
    }
}
