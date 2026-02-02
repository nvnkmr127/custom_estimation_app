<?php

namespace App\Livewire\Admin\Webhooks\Components;

use Livewire\Component;
use App\Webhooks\PayloadMapper;
use Illuminate\Support\Arr;

class PayloadMapperBuilder extends Component
{
    public $mapping = []; // The mapping config array
    public $samplePayloadJson = '';
    public $preview = [];
    public $activeTab = 'builder'; // builder | json

    protected $listeners = ['update-sample-payload' => 'updateSamplePayload'];

    public function mount($mapping = [], $samplePayload = [])
    {
        // Transform incoming $mapping (config format) into UI rows
        $this->mapping = $this->transformConfigToUi($mapping);
        $this->samplePayloadJson = json_encode($samplePayload, JSON_PRETTY_PRINT);
        $this->refreshPreview();
    }

    protected function transformConfigToUi($config)
    {
        if (empty($config))
            return [];

        $uiRows = [];
        foreach ($config as $target => $rule) {
            if (is_array($rule)) {
                $uiRows[] = [
                    'target' => $target,
                    'source' => $rule['source'] ?? '',
                    'default' => $rule['default'] ?? '',
                    'transform' => $rule['transform'] ?? '',
                    'is_advanced' => true
                ];
            } else {
                $uiRows[] = [
                    'target' => $target,
                    'source' => $rule,
                    'default' => '',
                    'transform' => '',
                    'is_advanced' => false
                ];
            }
        }
        return $uiRows;
    }

    public function updateSamplePayload($payload)
    {
        $this->samplePayloadJson = json_encode($payload, JSON_PRETTY_PRINT);
        $this->refreshPreview();
    }

    public function addRow()
    {
        $this->mapping[] = [
            'target' => '',
            'source' => '',
            'default' => '',
            'transform' => '',
            'is_advanced' => false // UI helper
        ];
        $this->refreshPreview();
    }

    public function removeRow($index)
    {
        unset($this->mapping[$index]);
        $this->mapping = array_values($this->mapping);
        $this->refreshPreview();
    }

    public function updateRowType($index, $isAdvanced)
    {
        $this->mapping[$index]['is_advanced'] = $isAdvanced;
        if (!$isAdvanced) {
            // Convert back to simple string structure implies loosing extra fields?
            // Actually, in our internal state for the UI, we should keep it uniform (array)
            // and only collapse it when saving/emitting.
            // But to simplify, let's keep the UI state as the "advanced" structure always,
            // and just condense it on output?
            // Or better: The UI state matches the structure $target => $rule.
            // But since Livewire works best with rigid arrays, we might need a transformation layer.
            // Let's use a flat array of objects for the UI:
            // [['target_key' => '...', 'source_key' => '...', ...]]
            // and map it back and forth.
        }
    }

    public function updated($property)
    {
        // If any part of the mapping array is updated, refresh the preview and notify parent
        if ($property === 'samplePayloadJson' || str_starts_with($property, 'mapping')) {
            $this->refreshPreview();
        }
    }

    public function refreshPreview()
    {
        $sample = json_decode($this->samplePayloadJson, true);
        if (!$sample) {
            $this->preview = ['error' => 'Invalid Sample JSON'];
            return;
        }

        // Convert UI mapping structure to Service mapping structure
        $config = $this->transformUiToConfig($this->mapping);

        try {
            $mapper = new PayloadMapper();
            $this->preview = $mapper->map($sample, $config);
            // Dispatch event to parent form to update its state
            $this->dispatch('mapping-updated', $config);
        } catch (\Exception $e) {
            $this->preview = ['error' => $e->getMessage()];
        }
    }

    // Convert flat UI array to nested generic config
    protected function transformUiToConfig($uiRows)
    {
        $config = [];
        foreach ($uiRows as $row) {
            if (empty($row['target']))
                continue;

            if (empty($row['default']) && empty($row['transform'])) {
                // Simple string mapping: 'target' => 'source'
                $config[$row['target']] = $row['source'];
            } else {
                // Advanced array mapping
                $config[$row['target']] = [
                    'source' => $row['source'],
                    'default' => $row['default'] ?: null,
                    'transform' => $row['transform'] ?: null,
                ];
            }
        }
        return $config;
    }

    public function dehydrate()
    {
        $config = $this->transformUiToConfig($this->mapping);
        $this->dispatch('mapping-updated', $config);
    }

    public function render()
    {
        return view('livewire.admin.webhooks.components.payload-mapper-builder');
    }
}
