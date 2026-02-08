<div>
    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Main Configuration -->
            <div class="lg:col-span-2 space-y-8">

                <!-- General Settings -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Endpoint Configuration</h3>
                        <p class="mt-1 text-sm text-gray-500">Define where and how webhooks are delivered.</p>
                    </div>
                    <div class="p-6 grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Name</label>
                            <div class="mt-2">
                                <input type="text" wire:model="state.name" id="name"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                            @error('state.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="status" class="block text-sm font-medium leading-6 text-gray-900">Status</label>
                            <div class="mt-2">
                                <select wire:model="state.status" id="status"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="disabled_by_system" disabled>Disabled by System</option>
                                </select>
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="url" class="block text-sm font-medium leading-6 text-gray-900">Target
                                URL</label>
                            <div class="mt-2 flex rounded-md shadow-sm">
                                <span
                                    class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 px-3 text-gray-500 sm:text-sm bg-gray-50">
                                    <select wire:model="state.http_method"
                                        class="h-full border-0 bg-transparent py-0 pl-1 pr-7 text-gray-500 font-semibold focus:ring-0 sm:text-sm">
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="PATCH">PATCH</option>
                                    </select>
                                </span>
                                <input type="text" wire:model="state.url" id="url"
                                    class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    placeholder="https://api.example.com/webhooks">
                            </div>
                            @error('state.url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-6">
                            <div class="flex items-center justify-between">
                                <label for="secret" class="block text-sm font-medium leading-6 text-gray-900">Signing
                                    Secret</label>
                                <button type="button" wire:click="regenerateSecret"
                                    class="text-xs text-indigo-600 hover:text-indigo-900">Regenerate</button>
                            </div>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <input type="{{ $showSecret ? 'text' : 'password' }}" wire:model="state.secret" readonly
                                    class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-gray-50 text-gray-500">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                    wire:click="$toggle('showSecret')">
                                    @if($showSecret)
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Used to sign payload with HMAC-SHA256
                                (`X-Webhook-Signature`).</p>
                        </div>
                    </div>

                    <!-- Headers Section -->
                    <div class="border-t border-gray-100 px-6 py-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-900">Custom Headers</h4>
                            <button type="button" wire:click="addHeader"
                                class="text-xs text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Add Header
                            </button>
                        </div>
                        <div class="space-y-3">
                            @foreach($state['headers'] as $index => $header)
                                <div class="flex items-center gap-2">
                                    <input type="text" wire:model="state.headers.{{ $index }}.key"
                                        placeholder="Key (e.g. Authorization)"
                                        class="block w-1/3 rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    <input type="text" wire:model="state.headers.{{ $index }}.value" placeholder="Value"
                                        class="block flex-1 rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">

                                    <button type="button" wire:click="removeHeader({{ $index }})"
                                        class="text-gray-400 hover:text-red-500">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                            @if(empty($state['headers']))
                                <p class="text-sm text-gray-400 italic">No custom headers configured.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Event Subscriptions -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Events</h3>
                            <p class="mt-1 text-sm text-gray-500">Select which events trigger this webhook.</p>
                        </div>
                        <button type="button" wire:click="subscribeAll"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-900">Select All</button>
                    </div>
                    <div class="p-6">
                        @error('state.events') <div class="mb-4 text-red-500 text-sm font-medium bg-red-50 p-3 rounded">
                        {{ $message }}</div> @enderror

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                            @foreach($groupedEvents as $group => $events)
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 mb-3 border-b border-gray-100 pb-1">
                                        {{ $group }}</h4>
                                    <div class="space-y-2">
                                        @foreach($events as $event)
                                            <div class="relative flex items-start">
                                                <div class="flex h-6 items-center">
                                                    <input id="event-{{ $event['name'] }}" wire:model="state.events"
                                                        value="{{ $event['name'] }}" type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                                </div>
                                                <div class="ml-3 text-sm leading-6">
                                                    <label for="event-{{ $event['name'] }}"
                                                        class="font-medium text-gray-900 select-none cursor-pointer">
                                                        {{ $event['name'] }}
                                                        @if(isset($eventStats[$event['name']]) && $eventStats[$event['name']] > 0)
                                                            <span
                                                                class="ml-1 inline-flex items-center rounded-full bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $eventStats[$event['name']] }}</span>
                                                        @endif
                                                    </label>
                                                    @if(!empty($event['description']))
                                                        <p class="text-gray-500 text-xs">{{ $event['description'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Advanced Options -->
                <div x-data="{ open: false }"
                    class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                    <button type="button"
                        class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition"
                        @click="open = !open">
                        <div class="text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Advanced Settings</h3>
                            <p class="mt-1 text-sm text-gray-500">Concurrency, rate limits, and retry policies.</p>
                        </div>
                        <svg class="h-5 w-5 text-gray-400 transform transition-transform"
                            :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open"
                        class="p-6 border-t border-gray-200 grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium leading-6 text-gray-900">Concurrency Limit</label>
                            <div class="mt-2">
                                <input type="number" wire:model="state.concurrency_limit" min="1" max="50"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Max parallel requests.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium leading-6 text-gray-900">Rate Limit (per
                                min)</label>
                            <div class="mt-2">
                                <input type="number" wire:model="state.rate_limit" min="1"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium leading-6 text-gray-900">Retry Delay (sec)</label>
                            <div class="mt-2">
                                <input type="number" wire:model="state.delay" min="0"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Initial delay before first attempt.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Testing & Metrics -->
            <div class="space-y-8">
                <!-- Actions -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6 sticky top-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <button type="submit"
                            class="w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            {{ $webhook && $webhook->exists ? 'Update Webhook' : 'Create Webhook' }}
                        </button>
                        <a href="{{ route('admin.webhooks.index') }}"
                            class="block w-full text-center rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</a>

                        @if($webhook && $webhook->exists)
                            <hr class="border-gray-100 my-4">
                            <button type="button" wire:confirm="Are you sure you want to delete this webhook?"
                                wire:click="$dispatch('deleteWebhook', {{ $webhook->id }})"
                                class="w-full text-red-600 hover:text-red-900 text-sm font-medium text-left flex items-center justify-center p-2 rounded hover:bg-red-50 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Webhook
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Test Connection -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Test Connection</h3>
                    <p class="text-sm text-gray-500 mb-4">Send a sample payload to verify connectivity.</p>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Sample Event Type</label>
                        <select wire:model.live="sampleEvent"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6">
                            <option value="">-- Generic Test Message --</option>
                            @foreach($groupedEvents as $group => $events)
                                <optgroup label="{{ $group }}">
                                    @foreach($events as $event)
                                        <option value="{{ $event['name'] }}">{{ $event['name'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                        class="w-full rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50">
                        <span wire:loading.remove wire:target="testConnection">Send Test Request</span>
                        <span wire:loading wire:target="testConnection">Sending...</span>
                    </button>

                    @if($testMessage)
                        <div
                            class="mt-4 rounded-md p-3 text-sm {{ $testStatus === 'success' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' }}">
                            {{ $testMessage }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>