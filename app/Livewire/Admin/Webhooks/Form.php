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

    protected $listeners = ['mapping-updated' => 'updateMapping'];

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
            $response = Http::timeout(5)->get($this->state['url']);

            if ($response->successful()) {
                $this->testStatus = 'success';
                $this->testMessage = "Connection successful (HTTP {$response->status()})";
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
        ]);

        // Transform headers back to key-value
        $headers = [];
        foreach ($this->state['headers'] as $header) {
            if (!empty($header['key'])) {
                $headers[$header['key']] = $header['value'];
            }
        }
        $this->state['headers'] = $headers;

        if ($this->webhook->exists) {
            $this->webhook->update($this->state);
            session()->flash('flash.banner', 'Webhook updated successfully.');
        } else {
            WebhookConfig::create($this->state);
            session()->flash('flash.banner', 'Webhook created successfully.');
        }

        return redirect()->route('admin.webhooks.index');
    }

    public function render()
    {
        $registry = app(WebhookEventRegistry::class);

        return view('livewire.admin.webhooks.form', [
            'groupedEvents' => $registry->getGroupedEvents(),
        ]);
    }
}
