<div>
    <form wire:submit="save">
        <div class="shadow sm:rounded-md sm:overflow-hidden">
            <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                <!-- Basic Info -->
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-input-label for="name" value="{{ __('Endpoint Name') }}" />
                        <x-text-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name"
                            placeholder="e.g. Production Data Sync" />
                        <x-input-error :messages="$errors->get('state.name')" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <x-input-label for="url" value="{{ __('Target URL') }}" />
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span
                                class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                https://
                            </span>
                            <x-text-input id="url" type="text" class="flex-1 block w-full rounded-none rounded-r-md"
                                wire:model="state.url" placeholder="example.com/hooks" />
                        </div>
                        <x-input-error :messages="$errors->get('state.url')" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-2">
                        <x-input-label for="http_method" value="{{ __('HTTP Method') }}" />
                        <select id="http_method" wire:model="state.http_method"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                        </select>
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="status" type="checkbox" wire:model="state.status" value="active"
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" {{ $state['status'] === 'active' ? 'checked' : '' }}
                                    wire:click="$set('state.status', $state['status'] === 'active' ? 'inactive' : 'active')">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="status" class="font-medium text-gray-700">Enable Endpoint</label>
                                <p class="text-gray-500">Uncheck to pause delivery of events.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200"></div>
                    </div>
                </div>

                <!-- Security -->
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Security</h3>
                    <p class="mt-1 text-sm text-gray-500">Configure how we authenticate with your server.</p>

                    <div class="mt-6 grid grid-cols-6 gap-6">
                        <div class="col-span-6 sm:col-span-4">
                            <x-input-label for="secret" value="{{ __('Signing Secret (HMAC-SHA256)') }}" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="{{ $showSecret ? 'text' : 'password' }}" wire:model="state.secret" readonly
                                    class="flex-1 block w-full rounded-none rounded-l-md border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                <button type="button" wire:click="$toggle('showSecret')"
                                    class="inline-flex items-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm hover:bg-gray-100">
                                    {{ $showSecret ? 'Hide' : 'Show' }}
                                </button>
                                <button type="button" wire:click="regenerateSecret"
                                    class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-white text-indigo-600 font-medium text-sm hover:text-indigo-500">
                                    Regenerate
                                </button>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Verify requests using the
                                <code>X-Webhook-Signature</code> header.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200"></div>
                    </div>
                </div>

                <!-- Events -->
                <div>
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Subscribed Events</h3>
                            <p class="mt-1 text-sm text-gray-500">Select which events trigger this webhook.</p>
                        </div>
                        <button type="button" wire:click="subscribeAll"
                            class="text-sm text-indigo-600 hover:text-indigo-900">Select All</button>
                    </div>

                    <div class="mt-6 space-y-4">
                        <x-input-error :messages="$errors->get('state.events')" class="mb-2" />

                        @foreach($groupedEvents as $group => $events)
                            <fieldset>
                                <legend class="text-base font-medium text-gray-900">{{ $group }}</legend>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    @foreach($events as $event)
                                        <div class="relative flex items-start">
                                            <div class="flex items-center h-5">
                                                <input id="event_{{ $event['name'] }}" name="events[]"
                                                    value="{{ $event['name'] }}" type="checkbox" wire:model="state.events"
                                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <label for="event_{{ $event['name'] }}"
                                                        class="font-medium text-gray-700">{{ $event['name'] }}</label>
                                                    @if(isset($eventStats[$event['name']]))
                                                        <span
                                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                            title="Occurred {{ $eventStats[$event['name']] }} times in last 24h">
                                                            <span
                                                                class="w-1 h-1 rounded-full bg-green-500 mr-1 animate-pulse"></span>
                                                            {{ $eventStats[$event['name']] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-gray-500">{{ $event['description'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </div>

                <!-- Advanced (Collapsed by default, shown here inline for simplicity) -->
                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200"></div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Advanced Settings</h3>

                    <div class="mt-6 grid grid-cols-6 gap-6">
                        <!-- Headers -->
                        <div class="col-span-6">
                            <label class="block text-sm font-medium text-gray-700">Custom Headers</label>
                            <div class="mt-2 space-y-2">
                                @foreach($state['headers'] as $index => $header)
                                    <div class="flex gap-2">
                                        <input type="text" wire:model="state.headers.{{ $index }}.key"
                                            placeholder="Key (e.g. Authorization)"
                                            class="flex-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <input type="text" wire:model="state.headers.{{ $index }}.value" placeholder="Value"
                                            class="flex-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        <button type="button" wire:click="removeHeader({{ $index }})"
                                            class="text-red-600 hover:text-red-900">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addHeader"
                                    class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">+ Add
                                    Header</button>
                            </div>
                        </div>

                        <!-- Rate Limit -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-input-label for="rate_limit" value="{{ __('Rate Limit (requests/min)') }}" />
                            <x-text-input id="rate_limit" type="number" wire:model="state.rate_limit"
                                class="mt-1 block w-full" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-input-label for="concurrency_limit" value="{{ __('Concurrency Limit') }}" />
                            <x-text-input id="concurrency_limit" type="number" wire:model="state.concurrency_limit"
                                class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="mt-8">
                        <div class="py-4">
                            <div class="border-t border-gray-200"></div>
                        </div>
                        <h4 class="text-md leading-6 font-medium text-gray-900 mb-4">Payload Transformation</h4>
                        <p class="text-sm text-gray-500 mb-4">Map internal event data to the structure expected by the
                            external service.</p>

                        <div class="mb-6 max-w-xs">
                            <label for="sample_event" class="block text-sm font-medium text-gray-700">Sample Payload
                                Basis</label>
                            <select id="sample_event" wire:model.live="sampleEvent"
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Generic Sample --</option>
                                @foreach($groupedEvents as $group => $events)
                                    <optgroup label="{{ $group }}">
                                        @foreach($events as $event)
                                            <option value="{{ $event['name'] }}">{{ $event['name'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400 italic">Select an event to use its actual structure as
                                your mapping reference.</p>
                        </div>

                        @livewire('admin.webhooks.components.payload-mapper-builder', [
                            'mapping' => $state['payload_mapping'] ?? [],
                            'samplePayload' => ['event' => 'order.created', 'data' => ['id' => 123, 'total' => 99.00, 'created_at' => now()->toIso8601String()]]
                        ], 'mapper-' . ($webhook->id ?? 'new'))
                    </div>
                </div>

        </div>
        <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 flex items-center justify-between">
            <div class="flex items-center">
                            <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900 mr-4">
                                <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                <span wire:loading wire:target="testConnection">Testing...</span>
                </button>
                @if($testStatus)
                            <span class="text-sm {{ $testStatus === 'success' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $testMessage }}
                    </span>
                @endif
                </div>

                <x-primary-button class="ml-3" wire:loading.attr="disabled">
                    {{ __('Save Endpoint') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</div>