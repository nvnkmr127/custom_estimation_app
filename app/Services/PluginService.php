<?php

namespace App\Services;

use App\Models\PluginModule;
use App\Models\PluginModuleLog;
use App\Models\Estimate;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PluginService
{
    /**
     * Dispatch an outbound webhook event for an active plugin module.
     */
    public function executeOutboundModule(PluginModule $module, array $eventPayload): void
    {
        if (!$module->is_active || !$module->plugin->is_active) {
            return;
        }

        $settings = $module->settings ?? [];
        $url = $settings['url'] ?? null;
        if (!$url) {
            Log::warning("PluginService: Outbound module {$module->name} has no target URL configured.");
            return;
        }

        $method = strtoupper($settings['method'] ?? 'POST');
        $pluginConfig = $module->plugin->config ?? [];

        // Dynamic placeholder replacement in URL and payload (e.g., {id}, {name})
        $flatPayload = $this->flattenArray($eventPayload);
        foreach ($flatPayload as $key => $val) {
            if (is_scalar($val)) {
                $url = str_replace('{' . $key . '}', $val, $url);
            }
        }

        // Custom Payload mapping (optional)
        $mappedPayload = $eventPayload;
        if (!empty($settings['payload_mapping'])) {
            $mappedPayload = [];
            foreach ($settings['payload_mapping'] as $destKey => $sourcePath) {
                $mappedPayload[$destKey] = $this->getValueByPath($eventPayload, $sourcePath);
            }
        }

        // Base payload construction
        $payload = [
            'event' => $module->event_name,
            'timestamp' => now()->toIso8601String(),
            'module_key' => $module->key,
            'plugin_key' => $module->plugin->key,
            'data' => $mappedPayload,
        ];

        // Format body for external system integrations (e.g. Slack blocks or plain webhook)
        if ($module->plugin->key === 'slack') {
            $webhookUrl = !empty($pluginConfig['webhook_url']) ? $pluginConfig['webhook_url'] : $url;
            $url = $webhookUrl;
            $estimateNumber = $flatPayload['estimate_number'] ?? 'N/A';
            $clientName = $flatPayload['client.name'] ?? $flatPayload['client_name'] ?? 'N/A';
            $totalAmount = $flatPayload['total'] ?? '0.00';
            
            $text = "🔔 *Estimation App Update* \nEvent: `{$module->event_name}`\nEstimate #: *{$estimateNumber}*\nClient: *{$clientName}*\nTotal: *₹{$totalAmount}*";
            $payload = [
                'text' => $text,
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $text
                        ]
                    ]
                ]
            ];
        }

        $jsonPayload = json_encode($payload);
        $headers = [
            'Content-Type' => 'application/json',
            'X-Plugin-Module' => $module->key,
        ];

        // Custom headers configured in settings
        if (!empty($settings['headers'])) {
            foreach ($settings['headers'] as $headerKey => $headerVal) {
                $headers[$headerKey] = $headerVal;
            }
        }

        // Secure Signature (if secret set in settings)
        $secret = $settings['secret'] ?? $pluginConfig['api_key'] ?? null;
        if ($secret) {
            $headers['X-Plugin-Signature'] = hash_hmac('sha256', $jsonPayload, $secret);
        }

        $startTime = microtime(true);
        try {
            $response = Http::withHeaders($headers)
                ->timeout(10);

            if ($method === 'POST') {
                $res = $response->post($url, $payload);
            } elseif ($method === 'PUT') {
                $res = $response->put($url, $payload);
            } else {
                $res = $response->get($url);
            }

            $latency = round((microtime(true) - $startTime) * 1000);

            PluginModuleLog::create([
                'plugin_module_id' => $module->id,
                'direction' => 'outbound',
                'status' => $res->successful() ? 'success' : 'failed',
                'payload' => $payload,
                'headers' => $headers,
                'response_code' => $res->status(),
                'response_body' => mb_substr($res->body(), 0, 1000),
                'latency_ms' => $latency,
            ]);

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            PluginModuleLog::create([
                'plugin_module_id' => $module->id,
                'direction' => 'outbound',
                'status' => 'failed',
                'payload' => $payload,
                'headers' => $headers,
                'response_code' => 0,
                'latency_ms' => $latency,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);
            Log::error("PluginService Outbound call error for module {$module->key}: " . $e->getMessage());
        }
    }

    /**
     * Process incoming webhook callback hitting a registered inbound plugin module.
     */
    public function processInboundWebhook(PluginModule $module, array $payload, array $headers): array
    {
        if (!$module->is_active || !$module->plugin->is_active) {
            return ['status' => 'ignored', 'message' => 'Module or Plugin is inactive.'];
        }

        $settings = $module->settings ?? [];
        $actionType = $settings['action_type'] ?? null;
        $actionConfig = $settings['action_config'] ?? [];

        $startTime = microtime(true);
        $status = 'success';
        $errorMessage = null;
        $responseBody = 'Processed successfully';

        try {
            switch ($actionType) {
                case 'update_estimate':
                    $idField = $actionConfig['identifier'] ?? 'id';
                    $idValue = $this->getValueByPath($payload, $idField);

                    if (!$idValue) {
                        throw new \Exception("Inbound webhook payload missing identifier at path: {$idField}");
                    }

                    // Look up estimate by ID or estimate number
                    $estimate = Estimate::where('id', $idValue)
                        ->orWhere('estimate_number', $idValue)
                        ->first();

                    if (!$estimate) {
                        throw new \Exception("Estimate matching ID/Number '{$idValue}' not found.");
                    }

                    $statusField = $actionConfig['status_field'] ?? 'status';
                    $payloadStatus = $this->getValueByPath($payload, $statusField);

                    if (!$payloadStatus) {
                        throw new \Exception("Inbound payload missing status field at path: {$statusField}");
                    }

                    $statusMap = $actionConfig['status_map'] ?? [];
                    $newStatus = $statusMap[$payloadStatus] ?? $payloadStatus;

                    // Execute transition using standard service or direct update
                    $stateService = app(\App\Services\Estimates\EstimateStateService::class);
                    try {
                        $stateService->transitionClientStatus($estimate, $newStatus);
                    } catch (\Exception $ex) {
                        $oldStatus = $estimate->estimate_status;
                        $estimate->update(['estimate_status' => $newStatus]);
                        
                        \App\Models\ActivityLog::log(
                            'status_updated', 
                            $estimate, 
                            "Estimate #{$estimate->estimate_number} status updated to {$newStatus} via Plugin webhook (Manual Override). Old: {$oldStatus}"
                        );
                    }
                    $responseBody = "Updated Estimate #{$estimate->estimate_number} status to '{$newStatus}'";
                    break;

                case 'create_lead':
                case 'create_client':
                    $clientData = [];
                    foreach ($actionConfig as $destField => $sourcePath) {
                        $val = $this->getValueByPath($payload, $sourcePath);
                        if ($val !== null) {
                            $clientData[$destField] = $val;
                        }
                    }

                    if (empty($clientData['email'])) {
                        throw new \Exception("Inbound payload missing mapped 'email' field for Client creation.");
                    }

                    if (empty($clientData['name'])) {
                        $clientData['name'] = 'Web Lead - ' . now()->format('Y-m-d');
                    }

                    DB::transaction(function() use ($clientData, &$responseBody) {
                        $client = Client::where('email', $clientData['email'])->lockForUpdate()->first();
                        if ($client) {
                            $client->update($clientData);
                            $responseBody = "Updated existing Client #{$client->id}";
                        } else {
                            $client = Client::create($clientData);
                            $responseBody = "Created new Client #{$client->id}";
                        }
                    });
                    break;

                default:
                    $responseBody = 'Webhook request received and logged.';
                    break;
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            $responseBody = 'Error: ' . $e->getMessage();
            Log::error("PluginService Inbound hook execution error: " . $e->getMessage());
        }

        $latency = round((microtime(true) - $startTime) * 1000);

        PluginModuleLog::create([
            'plugin_module_id' => $module->id,
            'direction' => 'inbound',
            'status' => $status,
            'payload' => $payload,
            'headers' => $headers,
            'response_code' => $status === 'success' ? 200 : 400,
            'response_body' => mb_substr($responseBody, 0, 1000),
            'latency_ms' => $latency,
            'error_message' => $errorMessage,
        ]);

        return [
            'status' => $status,
            'message' => $responseBody,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Resolve nested path value from array e.g. "customer.email" -> $array['customer']['email']
     */
    private function getValueByPath(array $data, string $path)
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

    /**
     * Flatten multi-dimensional array into single level dotted keys.
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
