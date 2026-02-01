<div>
    <div class="flex flex-col sm:flex-row justify-between mb-4 gap-4">
        <div class="flex-1">
            <x-text-input wire:model.live.debounce.300ms="search" type="text" placeholder="Search ID or Payload..."
                class="w-full" />
        </div>
        <div>
            <select wire:model.live="type"
                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">All Types</option>
                @foreach($types as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-text-input wire:model.live="dateFrom" type="date" class="w-full" />
        </div>
    </div>

    <div class="overflow-x-auto bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event ID
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occurred
                        At</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trace ID
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($events as $event)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ Str::limit($event->id, 8) }}...
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $event->event_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $event->occurred_at->format('M d, H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php $status = $event->delivery_status; @endphp
                            @if($status === 'success')
                                <span class="text-green-600 font-bold">Delivered</span>
                            @elseif($status === 'failed')
                                <span class="text-red-600 font-bold">Failed</span>
                            @elseif($status === 'partial')
                                <span class="text-yellow-600 font-bold">Partial</span>
                            @else
                                <span class="text-gray-500">Processing</span>
                            @endif
                            <span
                                class="text-xs text-gray-400 ml-1">({{ $event->deliveries->where('status', 'success')->count() }}/{{ $event->deliveries->count() }})</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono text-xs">
                            {{ $event->payload['meta']['trace_id'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.webhooks.events.show', $event) }}"
                                class="text-indigo-600 hover:text-indigo-900">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            No events found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $events->links() }}
    </div>
</div>