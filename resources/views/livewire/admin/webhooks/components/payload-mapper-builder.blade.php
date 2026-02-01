<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Builder Column -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-medium text-gray-700">Field Mappings</h3>
            <button wire:click="addRow" type="button"
                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                + Add Field
            </button>
        </div>

        <div class="space-y-2">
            @foreach($mapping as $index => $row)
                <div class="bg-gray-50 border border-gray-200 rounded-md p-3 relative group">
                    <button wire:click="removeRow({{ $index }})" type="button"
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="grid grid-cols-1 gap-y-2 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Target Field (External)</label>
                            <input type="text" wire:model.live.debounce.300ms="mapping.{{ $index }}.target"
                                placeholder="e.g. data.customer_id"
                                class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Source Field (Internal)</label>
                            <input type="text" wire:model.live.debounce.300ms="mapping.{{ $index }}.source"
                                placeholder="e.g. client.id"
                                class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Advanced Toggle / Fields -->
                    <div class="mt-2 grid grid-cols-1 gap-y-2 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Default Value</label>
                            <input type="text" wire:model.live.debounce.300ms="mapping.{{ $index }}.default"
                                placeholder="Optional"
                                class="mt-1 block w-full text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Transform</label>
                            <select wire:model.live="mapping.{{ $index }}.transform"
                                class="mt-1 block w-full text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">None</option>
                                <option value="date:Y-m-d">Date (YYYY-MM-DD)</option>
                                <option value="string">String</option>
                                <option value="int">Integer</option>
                                <option value="bool">Boolean</option>
                                <option value="json">To JSON String</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach

            @if(empty($mapping))
                <div class="text-center p-4 border-2 border-dashed border-gray-300 rounded-md text-gray-500 text-sm">
                    No mappings defined. The payload will be sent as-is, or define mappings to transform it.
                </div>
            @endif
        </div>
    </div>

    <!-- Preview Column -->
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sample Input (Internal Source)</label>
            <textarea wire:model.live.debounce.500ms="samplePayloadJson" rows="6"
                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs font-mono border-gray-300 rounded-md"
                placeholder="{ 'id': 123, ... }"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Transformed Output (Target)</label>
            <div class="bg-gray-800 rounded-md p-3 overflow-x-auto min-h-[200px]">
                @if(isset($preview['error']))
                    <div class="text-red-400 text-xs font-mono">{{ $preview['error'] }}</div>
                @else
                    <pre
                        class="text-green-400 text-xs font-mono">{{ json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </div>
        </div>
    </div>
</div>