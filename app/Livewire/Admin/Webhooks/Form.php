<?php

namespace App\Livewire\Admin\Webhooks;

use Livewire\Component;
use App\Models\WebhookConfig;
use Illuminate\Support\Str;
use App\Webhooks\WebhookEventRegistry;
use Illuminate\Support\Facades\Http;

class Form extends Component
{
    public ?WebhookConfig $webhook = null;

    public array $state = [
        'name' => '',
        'url' => '',
        'http_method' => 'POST',
        'status' => 'active',
        'secret' => '',
        'events' => [],
        'headers' => [], // Array of ['key' => '', 'value' => '']
        'concurrency_limit' => 5,
        'rate_limit' => 60,
        'delay' => 0,
    ];

    public bool $showSecret = false;
    public string $testStatus = '';
    public string $testMessage = '';
    public string $sampleEvent = '';

    protected $listeners = [
        'mapping-updated' => 'updateMapping',
        'update-sample-payload' => 'setSamplePayload'
    ];

    public function mount(WebhookConfig $webhook = null)
    {
        if ($webhook && $webhook->exists) {
            $this->webhook = $webhook;
            $this->state = $webhook->toArray();

            // Format headers for UI
            $formattedHeaders = [];
            foreach ($webhook->headers ?? [] as $key => $value) {
                $formattedHeaders[] = ['key' => $key, 'value' => $value];
            }
            $this->state['headers'] = $formattedHeaders;
        } else {
            $this->webhook = new WebhookConfig();
            $this->state = [
                'name' => '',
                'url' => '',
                'http_method' => 'POST',
                'status' => 'active',
                'secret' => 'whsec_' . Str::random(32),
                'events' => [],
                'headers' => [],
                'concurrency_limit' => 5,
                'rate_limit' => 60,
                'delay' => 0,
                'payload_mapping' => [],
            ];
        }
    }

    public function regenerateSecret()
    {
        $this->state['secret'] = 'whsec_' . Str::random(32);
        $this->showSecret = true;
    }

    public function updatedSampleEvent($value)
    {
        if (!$value)
            return;

        $registry = app(WebhookEventRegistry::class);
        $def = $registry->get($value);
        if ($def) {
            $this->dispatch('update-sample-payload', $def->samplePayload())->to('admin.webhooks.components.payload-mapper-builder');
        }
    }

    public function setSamplePayload($payload)
    {
        // This is a placeholder if we need it, but the dispatch above handles it.
    }

    public function updateMapping($mapping)
    {
        $this->state['payload_mapping'] = $mapping;
    }

    public function addHeader()
    {
        $this->state['headers'][] = ['key' => '', 'value' => ''];
    }

    public function removeHeader($index)
    {
        unset($this->state['headers'][$index]);
        $this->state['headers'] = array_values($this->state['headers']);
    }

    public function subscribeAll()
    {
        $registry = app(WebhookEventRegistry::class);
        $this->state['events'] = $registry->getEventNames();
    }

    public function testConnection()
    {
        $this->validate([
            'state.url' => 'required|url',
        ]);

        try {
            // Get Sample Payload
            $registry = app(WebhookEventRegistry::class);
            $samplePayload = [];

            if ($this->sampleEvent) {
                $def = $registry->get($this->sampleEvent);
                if ($def) {
                    $samplePayload = [
                        'event_id' => 'test_' . Str::random(10),
                        'event_type' => $def->name(),
                        'source' => 'system.test',
                        'occurred_at' => now()->toIso8601String(),
                        'payload' => $def->samplePayload(),
                    ];
                }
            } else {
                $samplePayload = [
                    'message' => 'Test connection from Webhook System',
                    'timestamp' => now()->toIso8601String(),
                    'source' => 'system.test'
                ];
            }

            // Apply Mapping if configured
            if (!empty($this->state['payload_mapping'])) {
                $mapper = app(\App\Webhooks\PayloadMapper::class);
                $payloadToSend = $mapper->map($samplePayload, $this->state['payload_mapping']);
            } else {
                $payloadToSend = $samplePayload;
            }

            $jsonPayload = json_encode($payloadToSend);
            $method = $this->state['http_method'] ?? 'POST';

            // Transform state headers for sending
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-Webhook-System-Tester/1.0',
            ];

            foreach ($this->state['headers'] as $header) {
                if (!empty($header['key'])) {
                    $headers[$header['key']] = $header['value'];
                }
            }

            if ($this->state['secret']) {
                $timestamp = time();
                $headers['X-Webhook-Timestamp'] = (string) $timestamp;
                $toSign = "{$timestamp}.{$jsonPayload}";
                $headers['X-Webhook-Signature'] = hash_hmac('sha256', $toSign, $this->state['secret']);
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->send($method, $this->state['url']);

            if ($response->successful()) {
                $this->testStatus = 'success';
                $this->testMessage = "Success! Sent to " . parse_url($this->state['url'], PHP_URL_HOST);
            } else {
                $this->testStatus = 'error';
                $this->testMessage = "Endpoint returned HTTP {$response->status()}";
            }
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = "Connection failed: " . $e->getMessage();
        }
    }

    public function save()
    {
        $this->validate([
            'state.name' => 'required|string|max:255',
            'state.url' => 'required|url|max:255',
            'state.http_method' => 'required|in:POST,PUT,PATCH',
            'state.status' => 'required|in:active,inactive,disabled_by_system',
            'state.secret' => 'nullable|string|max:255',
            'state.events' => 'required|array|min:1',
            'state.concurrency_limit' => 'integer|min:1|max:50',
            'state.rate_limit' => 'integer|min:1',
            'state.delay' => 'integer|min:0',
            'state.payload_mapping' => 'nullable|array',
            'state.headers' => 'array',
        ]);

        // Transform headers back to key-value
        $headers = [];
        foreach ($this->state['headers'] as $header) {
            if (!empty($header['key'])) {
                $headers[$header['key']] = $header['value'];
            }
        }

        $data = $this->state;
        $data['headers'] = $headers;

        if ($this->webhook->exists) {
            $this->webhook->update($data);
            session()->flash('flash.banner', 'Webhook updated successfully.');
        } else {
            WebhookConfig::create($data);
            session()->flash('flash.banner', 'Webhook created successfully.');
        }

        return redirect()->route('admin.webhooks.index');
    }

    public function render()
    {
        $registry = app(WebhookEventRegistry::class);

        // Fetch event distribution from EventLog for the last 24 hours
        $stats = \App\Models\EventLog::where('created_at', '>=', now()->subDay())
            ->select('event_name', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('event_name')
            ->pluck('count', 'event_name')
            ->toArray();

        return view('livewire.admin.webhooks.form', [
            'groupedEvents' => $registry->getGroupedEvents(),
            'eventStats' => $stats,
        ]);
    }
}
