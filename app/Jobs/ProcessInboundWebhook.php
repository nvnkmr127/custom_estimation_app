<?php

namespace App\Jobs;

use App\Models\WebhookInboundEvent;
use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected WebhookInboundEvent $event
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->event->status !== 'pending') {
            return;
        }

        try {
            $this->processByProvider();

            $this->event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook: Failed to process event #{$this->event->id}: " . $e->getMessage());

            $this->event->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function processByProvider(): void
    {
        if (\Illuminate\Support\Str::isUuid($this->event->provider)) {
            $this->handleGenericEndpoint();
            return;
        }

        match ($this->event->provider) {
            default => Log::info("Webhook: No specific handler for provider {$this->event->provider}"),
        };
    }

    protected function handleGenericEndpoint(): void
    {
        $endpoint = \App\Models\WebhookInboundEndpoint::where('uuid', $this->event->provider)->first();
        if (!$endpoint) {
            throw new \Exception("Endpoint not found for UUID: {$this->event->provider}");
        }

        $mappers = $endpoint->mappers()->where('is_active', true)->get();
        $payload = $this->event->payload;
        $processedCount = 0;

        foreach ($mappers as $mapper) {
            if ($this->checkTrigger($mapper->trigger_rules, $payload)) {
                $this->executeAction($mapper, $payload);
                $processedCount++;
            }
        }

        if ($processedCount === 0) {
            Log::info("Webhook: Event received but no mappers triggered for endpoint {$endpoint->name}");
        }
    }

    protected function checkTrigger(?array $rules, array $payload): bool
    {
        // If no rules, assume always trigger? Or never? Let's say never to be safe, or allow "catch all" if empty.
        // For now, if empty, we skip.
        if (empty($rules))
            return true;

        $field = $rules['field'] ?? null;
        $value = $rules['value'] ?? null;
        $operator = $rules['operator'] ?? '=';

        if (!$field)
            return true; // Empty rule = match all

        $actualValue = $this->getValue($payload, $field);

        return match ($operator) {
            '=' => $actualValue == $value,
            '!=' => $actualValue != $value,
            'contains' => str_contains((string) $actualValue, (string) $value),
            '>' => $actualValue > $value,
            '<' => $actualValue < $value,
            default => false,
        };
    }

    protected function executeAction(\App\Models\WebhookMapper $mapper, array $payload): void
    {
        Log::info("Webhook: Executing Mapper '{$mapper->name}' ({$mapper->action_type})");

        $config = $mapper->action_config ?? [];

        switch ($mapper->action_type) {
            case 'update_estimate':
                // Expecting config to map "estimate_id" -> "payload.id" etc
                $idField = $config['identifier'] ?? 'id'; // Key in payload that holds the ID
                $idValue = $this->getValue($payload, $idField);

                if ($idValue) {
                    $estimate = Estimate::find($idValue);
                    if ($estimate) {
                        // Map status if configured
                        $statusMap = $config['status_map'] ?? []; // e.g. {"paid": "accepted"}
                        $payloadStatus = $this->getValue($payload, $config['status_field'] ?? 'status');

                        $newStatus = $statusMap[$payloadStatus] ?? $payloadStatus;

                        $estimate->update(['status' => $newStatus]);
                        Log::info("Webhook: Updated Estimate #{$idValue} to status {$newStatus}");
                    } else {
                        Log::warning("Webhook: Estimate #{$idValue} not found.");
                    }
                }
                break;

            case 'create_lead':
                Log::info("Webhook: 'Create Lead' action triggered.", ['payload_snippet' => array_keys($payload)]);

                $clientData = [];
                foreach ($config as $destField => $sourcePath) {
                    // The UI adds 'payload.' prefix, but we are working with the payload array directly
                    // So we strip 'payload.' to find the value in the array
                    $cleanPath = str_replace('payload.', '', $sourcePath);
                    $value = $this->getValue($payload, $cleanPath);

                    if ($value !== null) {
                        $clientData[$destField] = $value;
                    }
                }

                if (empty($clientData)) {
                    Log::warning("Webhook: No data-mapped fields found for Create Lead action.");
                    break;
                }

                // Try to find existing client by unique identifiers to avoid duplicates
                $client = null;
                if (!empty($clientData['email'])) {
                    $client = \App\Models\Client::where('email', $clientData['email'])->first();
                }

                if ($client) {
                    $client->update($clientData);
                    Log::info("Webhook: Updated existing Client #{$client->id} ({$client->email})");
                } else {
                    $client = \App\Models\Client::create($clientData);
                    Log::info("Webhook: Created new Client #{$client->id}");
                }
                break;

            case 'notify_slack':
                Log::info("Webhook: 'Notify Slack' action triggered.");
                break;
        }
    }

    protected function getValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data = $data[$key];
            } else {
                return null;
            }
        }
        return $data;
    }

}
