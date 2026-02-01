<div>
    <div class="mb-4">
        <h3 class="text-lg font-medium text-gray-900">Incident Inbox</h3>
        <p class="text-sm text-gray-500">Manage failed webhooks that have exhausted all retry attempts.</p>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($deadLetters as $letter)
                <li>
                    <div class="block hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-indigo-600 truncate">
                                    {{ $letter->webhook->name ?? 'Unknown Endpoint' }}
                                </p>
                                <div class="ml-2 flex-shrink-0 flex">
                                    <p
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $letter->replayed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $letter->replayed ? 'Resolved' : 'Failed' }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 sm:flex sm:justify-between">
                                <div class="sm:flex">
                                    <p class="flex items-center text-sm text-gray-500">
                                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ Str::limit($letter->final_error_message, 80) }}
                                    </p>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                    <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p>
                                        Failed <time
                                            datetime="{{ $letter->failed_at }}">{{ $letter->failed_at->diffForHumans() }}</time>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-400">
                                Event Type: {{ $letter->originalDelivery->event->event_type ?? 'N/A' }} | Attempted
                                {{ $letter->originalDelivery->attempt ?? '?' }} times
                            </div>
                            <div class="mt-4 flex space-x-3">
                                @if(!$letter->replayed)
                                    <button wire:click="replay('{{ $letter->id }}')"
                                        class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Replay
                                    </button>
                                    <button wire:click="editAndReplay('{{ $letter->id }}')"
                                        class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                        Edit & Replay
                                    </button>
                                    <button wire:click="delete('{{ $letter->id }}')"
                                        class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        Ignore/Delete
                                    </button>
                                @else
                                    <span class="text-sm text-gray-500 italic">No actions available</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    No dead letters found. Everything is running smoothly!
                </li>
            @endforelse
        </ul>
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $deadLetters->links() }}
        </div>
    </div>

    <!-- Edit Payload Modal -->
    <x-modal name="edit-payload" wire:model.live="editingId" maxWidth="lg">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Edit Payload & Replay</h3>
            <p class="text-sm text-gray-500 mb-4">Modify the JSON payload before re-queuing the event. This will create
                a new event record.</p>

            <textarea wire:model="payloadJson" rows="10"
                class="w-full font-mono text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
            @error('payloadJson') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button" wire:click="confirmReplay"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                Replay Now
            </button>
            <button type="button" x-on:click="$dispatch('close-modal', 'edit-payload')"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                Cancel
            </button>
        </div>
    </x-modal>
</div>