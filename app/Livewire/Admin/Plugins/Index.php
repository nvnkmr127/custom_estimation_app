<?php

namespace App\Livewire\Admin\Plugins;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Plugin;
use App\Models\PluginModule;
use App\Models\PluginModuleLog;
use Illuminate\Support\Str;

class Index extends Component
{
    use WithPagination;

    // Active tab: 'plugins' or 'logs'
    public $activeTab = 'plugins';

    // Search and filters for logs
    public $logSearch = '';
    public $logStatus = '';
    public $logDirection = '';

    // Modals visibility toggles
    public $showCreatePluginModal = false;
    public $showConfigModal = false;
    public $showModuleModal = false;
    public $showLogModal = false;

    // Selected items
    public $selectedPluginId = null;
    public $selectedModuleId = null;
    public $selectedLogId = null;

    // Form inputs for Plugin creation
    public $pluginName = '';
    public $pluginKey = '';
    public $pluginDescription = '';
    public $pluginVersion = '1.0.0';

    // Form inputs for Plugin Config
    public $pluginConfig = []; // associative array based on config_schema

    // Form inputs for Module
    public $moduleName = '';
    public $moduleKey = '';
    public $moduleType = 'outbound'; // 'outbound' or 'inbound'
    public $moduleEventName = 'estimate.approved';
    public $moduleIsActive = true;
    public $moduleUrl = '';
    public $moduleMethod = 'POST';
    public $moduleSecret = '';
    public $moduleActionType = 'update_estimate'; // For inbound action mapping
    public $moduleHeadersInput = ''; // Textarea for JSON headers
    public $moduleMappingsInput = ''; // Textarea for custom JSON mappings

    protected $rules = [
        'pluginName' => 'required|string|max:255',
        'pluginKey' => 'required|alpha_dash|unique:plugins,key',
        'pluginVersion' => 'required|string',
    ];

    public function mount()
    {
        // Require the manage_plugins capability
        $user = auth()->user();
        if (!$user || !$user->hasPermission('manage_plugins')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function selectTab($tab)
    {
        $this->activeTab = $tab;
    }

    // --- Plugin Lifecycle Methods ---

    public function togglePluginStatus($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->update([
            'is_active' => !$plugin->is_active
        ]);
        session()->flash('success', "Plugin '{$plugin->name}' " . ($plugin->is_active ? 'activated' : 'deactivated') . " successfully.");
    }

    public function openCreatePlugin()
    {
        $this->pluginName = '';
        $this->pluginKey = '';
        $this->pluginDescription = '';
        $this->pluginVersion = '1.0.0';
        $this->showCreatePluginModal = true;
    }

    public function createPlugin()
    {
        $this->validate();

        $plugin = Plugin::create([
            'name' => $this->pluginName,
            'key' => Str::lower($this->pluginKey),
            'description' => $this->pluginDescription,
            'version' => $this->pluginVersion,
            'is_active' => false,
            'config_schema' => [],
            'config' => [],
        ]);

        $this->showCreatePluginModal = false;
        session()->flash('success', "Plugin '{$plugin->name}' created successfully.");
    }

    public function openConfigPlugin($id)
    {
        $this->selectedPluginId = $id;
        $plugin = Plugin::findOrFail($id);
        
        $this->pluginConfig = $plugin->config ?? [];
        foreach ($plugin->config_schema ?? [] as $field) {
            if (!isset($this->pluginConfig[$field['name']])) {
                $this->pluginConfig[$field['name']] = '';
            }
        }
        
        $this->showConfigModal = true;
    }

    public function saveConfig()
    {
        $plugin = Plugin::findOrFail($this->selectedPluginId);
        $plugin->update([
            'config' => $this->pluginConfig
        ]);
        $this->showConfigModal = false;
        session()->flash('success', "Plugin '{$plugin->name}' configuration saved.");
    }

    public function deletePlugin($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->delete();
        session()->flash('success', "Plugin deleted successfully.");
    }

    // --- Module Lifecycle Methods ---

    public function openCreateModule($pluginId)
    {
        $this->selectedPluginId = $pluginId;
        $this->selectedModuleId = null;
        $this->moduleName = '';
        $this->moduleKey = '';
        $this->moduleType = 'outbound';
        $this->moduleEventName = 'estimate.approved';
        $this->moduleIsActive = true;
        $this->moduleUrl = '';
        $this->moduleMethod = 'POST';
        $this->moduleSecret = '';
        $this->moduleActionType = 'update_estimate';
        $this->moduleHeadersInput = "{\n  \"Accept\": \"application/json\"\n}";
        $this->moduleMappingsInput = "{\n  \"payload.id\": \"id\",\n  \"payload.status\": \"status\"\n}";
        
        $this->showModuleModal = true;
    }

    public function openEditModule($moduleId)
    {
        $this->selectedModuleId = $moduleId;
        $module = PluginModule::findOrFail($moduleId);
        $this->selectedPluginId = $module->plugin_id;

        $this->moduleName = $module->name;
        $this->moduleKey = $module->key;
        $this->moduleType = $module->type;
        $this->moduleEventName = $module->event_name ?? 'estimate.approved';
        $this->moduleIsActive = $module->is_active;

        $settings = $module->settings ?? [];
        $this->moduleUrl = $settings['url'] ?? '';
        $this->moduleMethod = $settings['method'] ?? 'POST';
        $this->moduleSecret = $settings['secret'] ?? '';
        $this->moduleActionType = $settings['action_type'] ?? 'update_estimate';
        
        $headers = $settings['headers'] ?? ['Accept' => 'application/json'];
        $this->moduleHeadersInput = json_encode($headers, JSON_PRETTY_PRINT);

        $mappings = $settings['action_config'] ?? $settings['payload_mapping'] ?? [];
        $this->moduleMappingsInput = json_encode($mappings, JSON_PRETTY_PRINT);

        $this->showModuleModal = true;
    }

    public function saveModule()
    {
        $this->validate([
            'moduleName' => 'required|string|max:255',
            'moduleKey' => 'required|alpha_dash|unique:plugin_modules,key,' . ($this->selectedModuleId ?: 'NULL'),
        ]);

        $headers = [];
        if (!empty($this->moduleHeadersInput)) {
            $headers = json_decode($this->moduleHeadersInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('moduleHeadersInput', 'Invalid JSON format: ' . json_last_error_msg());
                return;
            }
        }

        $mappings = [];
        if (!empty($this->moduleMappingsInput)) {
            $mappings = json_decode($this->moduleMappingsInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('moduleMappingsInput', 'Invalid JSON format: ' . json_last_error_msg());
                return;
            }
        }

        $settings = [
            'url' => $this->moduleUrl,
            'method' => $this->moduleMethod,
            'secret' => $this->moduleSecret,
            'headers' => $headers,
        ];

        if ($this->moduleType === 'inbound') {
            $settings['action_type'] = $this->moduleActionType;
            $settings['action_config'] = $mappings;
        } else {
            $settings['payload_mapping'] = $mappings;
        }

        $data = [
            'plugin_id' => $this->selectedPluginId,
            'name' => $this->moduleName,
            'key' => Str::lower($this->moduleKey),
            'type' => $this->moduleType,
            'event_name' => $this->moduleType === 'outbound' ? $this->moduleEventName : null,
            'is_active' => $this->moduleIsActive,
            'settings' => $settings,
        ];

        if ($this->selectedModuleId) {
            $module = PluginModule::findOrFail($this->selectedModuleId);
            $module->update($data);
            $msg = "Module updated successfully.";
        } else {
            $module = PluginModule::create($data);
            $msg = "Module created successfully.";
        }

        $this->showModuleModal = false;
        session()->flash('success', $msg);
    }

    public function toggleModuleStatus($id)
    {
        $module = PluginModule::findOrFail($id);
        $module->update([
            'is_active' => !$module->is_active
        ]);
        session()->flash('success', "Module '{$module->name}' state updated.");
    }

    public function deleteModule($id)
    {
        $module = PluginModule::findOrFail($id);
        $module->delete();
        session()->flash('success', "Module deleted successfully.");
    }

    // --- Log Details ---

    public function showLogDetails($logId)
    {
        $this->selectedLogId = $logId;
        $this->showLogModal = true;
    }

    public function render()
    {
        $plugins = Plugin::withCount(['modules' => function ($q) {
            $q->where('is_active', true);
        }])->get();

        $logsQuery = PluginModuleLog::with('module.plugin')->latest();

        if ($this->logSearch) {
            $logsQuery->where(function ($q) {
                $q->where('error_message', 'like', '%' . $this->logSearch . '%')
                  ->orWhere('response_body', 'like', '%' . $this->logSearch . '%')
                  ->orWhereHas('module', function ($qm) {
                      $qm->where('name', 'like', '%' . $this->logSearch . '%')
                         ->orWhere('key', 'like', '%' . $this->logSearch . '%')
                         ->orWhereHas('plugin', function ($qp) {
                             $qp->where('name', 'like', '%' . $this->logSearch . '%');
                         });
                  });
            });
        }

        if ($this->logStatus) {
            $logsQuery->where('status', $this->logStatus);
        }

        if ($this->logDirection) {
            $logsQuery->where('direction', $this->logDirection);
        }

        $selectedLog = $this->selectedLogId ? PluginModuleLog::with('module.plugin')->find($this->selectedLogId) : null;

        return view('livewire.admin.plugins.index', [
            'plugins' => $plugins,
            'logs' => $logsQuery->paginate(15),
            'selectedLog' => $selectedLog,
            'systemEvents' => [
                'estimate.created' => 'Estimate Created',
                'estimate.updated' => 'Estimate Updated',
                'estimate.submitted_for_approval' => 'Estimate Submitted for Approval',
                'estimate.approved' => 'Estimate Approved',
                'estimate.rejected' => 'Estimate Rejected',
                'estimate.accepted' => 'Estimate Accepted/Signed',
                'estimate.declined' => 'Estimate Declined',
                'estimate.sent' => 'Estimate Sent to Client',
                'comment.created' => 'Comment Added',
                'user.created' => 'Staff Account Created',
            ]
        ])->layout('layouts.app');
    }
}
