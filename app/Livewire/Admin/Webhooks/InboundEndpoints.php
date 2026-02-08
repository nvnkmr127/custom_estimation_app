<?php

namespace App\Livewire\Admin\Webhooks;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WebhookInboundEndpoint;
use App\Models\WebhookInboundEvent;
use Illuminate\Support\Str;

class InboundEndpoints extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $newName = '';
    public $newDescription = '';

    public $selectedEndpointUuid = null;

    // Inspector State
    public $selectedEvent = null;
    public $showInspector = false;

    // Security Configuration State
    public $showSecurityModal = false;
    public $securityEndpointId = null;
    public $securitySecret = '';
    public $securitySignatureHeader = '';
    public $securityIpWhitelist = '';

    protected $rules = [
        'newName' => 'required|min:3|max:255',
        'newDescription' => 'nullable|string|max:1000',
    ];

    public function create()
    {
        $this->validate();

        WebhookInboundEndpoint::create([
            'uuid' => Str::uuid(),
            'name' => $this->newName,
            'description' => $this->newDescription,
            'is_active' => true,
        ]);

        $this->reset(['newName', 'newDescription', 'showCreateModal']);
        session()->flash('success', 'Endpoint created successfully.');
    }

    public function delete($id)
    {
        $endpoint = WebhookInboundEndpoint::findOrFail($id);
        $endpoint->delete();
        session()->flash('success', 'Endpoint deleted.');
    }

    public function toggleActive($id)
    {
        $endpoint = WebhookInboundEndpoint::findOrFail($id);
        $endpoint->is_active = !$endpoint->is_active;
        $endpoint->save();
    }

    // Edit State
    public $showEditModal = false;
    public $editingEndpointId = null;
    public $editName = '';
    public $editDescription = '';

    public function edit($id)
    {
        $endpoint = WebhookInboundEndpoint::findOrFail($id);
        $this->editingEndpointId = $endpoint->id;
        $this->editName = $endpoint->name;
        $this->editDescription = $endpoint->description;
        $this->showEditModal = true;
    }

    public function update()
    {
        $this->validate([
            'editName' => 'required|min:3|max:255',
            'editDescription' => 'nullable|string|max:1000',
        ]);

        $endpoint = WebhookInboundEndpoint::findOrFail($this->editingEndpointId);
        $endpoint->update([
            'name' => $this->editName,
            'description' => $this->editDescription,
        ]);

        $this->reset(['editName', 'editDescription', 'editingEndpointId', 'showEditModal']);
        session()->flash('success', 'Endpoint updated successfully.');
    }

    // Inspector Methods
    public function inspectEvent($id)
    {
        $this->selectedEvent = WebhookInboundEvent::findOrFail($id);
        $this->showInspector = true;
    }

    public function closeInspector()
    {
        $this->showInspector = false;
        $this->selectedEvent = null;
    }

    public function replayEvent($id)
    {
        $event = WebhookInboundEvent::findOrFail($id);
        $event->replay();
        session()->flash('success', 'Event re-queued for processing.');
        $this->inspectEvent($id); // Refresh inspector
    }

    // Security Methods
    public function editSecurity($id)
    {
        $endpoint = WebhookInboundEndpoint::findOrFail($id);
        $this->securityEndpointId = $endpoint->id;
        $this->securitySecret = $endpoint->secret;
        $this->securitySignatureHeader = $endpoint->signature_header;
        $this->securityIpWhitelist = $endpoint->ip_whitelist;
        $this->showSecurityModal = true;
    }

    public function saveSecurity()
    {
        $endpoint = WebhookInboundEndpoint::findOrFail($this->securityEndpointId);

        $endpoint->update([
            'secret' => $this->securitySecret,
            'signature_header' => $this->securitySignatureHeader,
            'ip_whitelist' => $this->securityIpWhitelist,
        ]);

        $this->showSecurityModal = false;
        session()->flash('success', 'Security settings updated.');
    }

    // Mapper State
    public $showMapperModal = false;
    public $mapperEndpointId = null;
    public $mappers = [];
    public $newMapperName = '';

    public $newMapperActionType = 'create_lead';

    // Visual Mapping State
    public $mappingRows = [];
    public $availablePayloadKeys = [];
    public $systemFields = [];

    // Trigger Rule State
    public $triggerField = '';
    public $triggerOperator = '=';
    public $triggerValue = '';

    public function manageMappers($id)
    {
        $this->mapperEndpointId = $id;
        $this->loadMappers();
        $this->startNewMapper(); // Reset form
        $this->showMapperModal = true;
    }

    public function startNewMapper()
    {
        $this->newMapperName = '';

        // Reset Trigger State
        $this->triggerField = '';
        $this->triggerOperator = '=';
        $this->triggerValue = '';

        $this->newMapperActionType = 'create_lead';
        $this->mappingRows = [['source' => '', 'destination' => '']];
        $this->availablePayloadKeys = [];
        $this->updateSystemFields();
    }

    public function loadMappers()
    {
        $this->mappers = \App\Models\WebhookMapper::where('webhook_inbound_endpoint_id', $this->mapperEndpointId)->get();
    }

    public function updatedNewMapperActionType()
    {
        $this->updateSystemFields();

        // Auto-populate rows if create_lead
        if ($this->newMapperActionType === 'create_lead' && !empty($this->systemFields)) {
            $this->mappingRows = [];
            foreach ($this->systemFields as $key => $label) {
                $this->mappingRows[] = [
                    'source' => '', // User must still select source
                    'destination' => $key
                ];
            }
        } else {
            // Reset to empty single row for others
            $this->mappingRows = [['source' => '', 'destination' => '']];
        }
    }

    public function updateSystemFields()
    {
        $this->systemFields = match ($this->newMapperActionType) {
            'create_lead' => [
                'name' => 'Name',
                'company' => 'Company',
                'email' => 'Email',
                'secondary_email' => 'Alternative Email',
                'phone' => 'Phone',
                'secondary_phone' => 'Alternative Phone',
                'property_name' => 'Property Name',
                'property_type' => 'Property Type',
                'address' => 'Street Address',
                'property_address' => 'Property Address',
                'city' => 'City',
                // 'notes' => 'Notes', // Removed from create view so optional
            ],
            'update_estimate' => [
                'identifier' => 'Estimate ID (Match)',
                'status' => 'New Status',
                'amount' => 'Amount',
                'notes' => 'Notes',
            ],
            'notify_slack' => [
                'channel' => 'Channel',
                'message' => 'Message Body',
            ],
            default => []
        };
    }

    public function discoverKeys()
    {
        $endpoint = WebhookInboundEndpoint::find($this->mapperEndpointId);
        $lastEvent = WebhookInboundEvent::where('provider', $endpoint->uuid)->latest()->first();

        if (!$lastEvent) {
            session()->flash('warning', 'No events found to analyze. Send a test webhook first!');
            return;
        }



        // Helper to flatten keys. The above one-liner is pseudo-code, let's do it properly.
        $this->availablePayloadKeys = $this->flattenArray($lastEvent->payload);

        if (empty($this->availablePayloadKeys)) {
            session()->flash('warning', 'Payload is empty or could not be parsed.');
        } else {
            session()->flash('success', 'Keys discovered from latest event!');
        }
    }

    protected function flattenArray($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value) && !empty($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[] = $newKey;
            }
        }
        return $result;
    }

    public function addMappingRow()
    {
        $this->mappingRows[] = ['source' => '', 'destination' => ''];
    }

    public function removeMappingRow($index)
    {
        unset($this->mappingRows[$index]);
        $this->mappingRows = array_values($this->mappingRows);
    }

    public function createMapper()
    {
        $this->validate([
            'newMapperName' => 'required|string|max:255',
            'newMapperActionType' => 'required|string',
        ]);

        // Convert mapping rows to config array
        $actionConfig = [];
        foreach ($this->mappingRows as $row) {
            if (!empty($row['source']) && !empty($row['destination'])) {
                // Determine structure based on action config requirements
                // For simplicity, we'll store a direct map: destination => source path
                $actionConfig[$row['destination']] = 'payload.' . $row['source'];
            }
        }

        // Construct structured trigger rules
        $triggerRules = null;
        if (!empty($this->triggerField)) {
            $triggerRules = [
                'field' => $this->triggerField,
                'operator' => $this->triggerOperator,
                'value' => $this->triggerValue,
            ];
        }

        \App\Models\WebhookMapper::create([
            'uuid' => Str::uuid(),
            'webhook_inbound_endpoint_id' => $this->mapperEndpointId,
            'name' => $this->newMapperName,
            'trigger_rules' => $triggerRules,
            'action_type' => $this->newMapperActionType,
            'action_config' => $actionConfig, // Saved as array, cast to JSON automatically
            'is_active' => true,
        ]);

        $this->startNewMapper();
        $this->loadMappers();
        session()->flash('success', 'Mapper created successfully.');
    }

    public $dryRunResult = null;

    public function dryRun()
    {
        $this->dryRunResult = null;

        // 1. Fetch Latest Event
        $endpoint = WebhookInboundEndpoint::find($this->mapperEndpointId);
        $lastEvent = WebhookInboundEvent::where('provider', $endpoint->uuid)->latest()->first();

        if (!$lastEvent) {
            $this->dryRunResult = ['type' => 'error', 'message' => 'No events found to test against.'];
            return;
        }

        // 2. Build Config exactly as createMapper does
        $actionConfig = [];
        foreach ($this->mappingRows as $row) {
            if (!empty($row['source']) && !empty($row['destination'])) {
                $actionConfig[$row['destination']] = 'payload.' . $row['source'];
            }
        }

        // 3. Create Temporary Mapper Object (not saved to DB)
        $triggerRules = null;
        if (!empty($this->triggerField)) {
            $triggerRules = [
                'field' => $this->triggerField,
                'operator' => $this->triggerOperator,
                'value' => $this->triggerValue,
            ];
        }

        $tempMapper = new \App\Models\WebhookMapper([
            'name' => $this->newMapperName,
            'action_type' => $this->newMapperActionType,
            'trigger_rules' => $triggerRules,
            'action_config' => $actionConfig,
        ]);

        // 4. Simulate Trigger Check
        $triggerRules = $tempMapper->trigger_rules;
        $matchesTrigger = $this->simulateCheckTrigger($triggerRules, $lastEvent->payload);

        if (!$matchesTrigger) {
            $this->dryRunResult = ['type' => 'warning', 'message' => 'Trigger Rules did NOT match the latest event payload. Action would skipped.'];
            return;
        }

        // 5. Simulate Action Execution (Data Mapping Verification)
        $mappedData = [];
        foreach ($actionConfig as $dest => $sourcePath) {
            // Remove 'payload.' prefix for helper compatibility if key contains it, or handle it in helper
            $cleanPath = str_replace('payload.', '', $sourcePath);
            $value = \Illuminate\Support\Arr::get($lastEvent->payload, $cleanPath);
            $mappedData[$dest] = $value; // Can be null
        }

        $this->dryRunResult = [
            'type' => 'success',
            'message' => 'Trigger Matched! Here is the data that would be processed:',
            'data' => $mappedData
        ];
    }

    protected function simulateCheckTrigger($rules, $payload)
    {
        if (empty($rules))
            return true;

        $field = $rules['field'] ?? null;
        $value = $rules['value'] ?? null;
        $operator = $rules['operator'] ?? '=';

        if (!$field)
            return true;

        $actualValue = \Illuminate\Support\Arr::get($payload, $field);

        return match ($operator) {
            '=' => $actualValue == $value,
            '!=' => $actualValue != $value,
            'contains' => str_contains((string) $actualValue, (string) $value),
            '>' => $actualValue > $value,
            '<' => $actualValue < $value,
            default => false,
        };
    }

    public function deleteMapper($id)
    {
        \App\Models\WebhookMapper::findOrFail($id)->delete();
        $this->loadMappers();
        session()->flash('success', 'Mapper deleted.');
    }

    public function render()
    {
        $eventsQuery = WebhookInboundEvent::whereIn('provider', WebhookInboundEndpoint::pluck('uuid'))->latest();

        if ($this->selectedEndpointUuid) {
            $eventsQuery->where('provider', $this->selectedEndpointUuid);
        }

        return view('livewire.admin.webhooks.inbound-endpoints', [
            'endpoints' => WebhookInboundEndpoint::latest()->get(),
            'recentEvents' => $eventsQuery->paginate(10),
        ]);
    }
}
