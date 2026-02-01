<div>
    <div class="flex justify-between mb-4">
        <div class="flex space-x-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search endpoints..."
                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">

            <select wire:model.live="status"
                class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Disabled</option>
                <option value="disabled_by_system">Circuit Open</option>
            </select>
        </div>

        <a href="{{ route('admin.webhooks.create') }}"
            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 font-medium text-sm">New Endpoint</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('name')">
                        Name
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        URL
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Events
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Health
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Delivery
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($webhooks as $webhook)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $webhook->name }}
                                </div>
                                @if($webhook->status === 'active')
                                    <span
                                        class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @elseif($webhook->status === 'inactive')
                                    <span
                                        class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Disabled
                                    </span>
                                @else
                                    <span
                                        class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ ucfirst(str_replace('_', ' ', $webhook->status)) }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ Str::mask($webhook->url, '*', 8, -5) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ count($webhook->events ?? []) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $rate = $webhook->success_rate;
                                $color = $rate > 95 ? 'text-green-600' : ($rate > 80 ? 'text-yellow-600' : 'text-red-600');
                            @endphp
                            <div class="text-sm font-bold {{ $color }}">
                                {{ $rate }}%
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($webhook->latestDelivery)
                                <div>{{ $webhook->latestDelivery->created_at->diffForHumans() }}</div>
                                <div
                                    class="text-xs {{ $webhook->latestDelivery->status === 'success' ? 'text-green-500' : 'text-red-500' }}">
                                    {{ ucfirst($webhook->latestDelivery->status) }}
                                </div>
                            @else
                                <span class="text-gray-400">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="toggleStatus('{{ $webhook->id }}')"
                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                {{ $webhook->status === 'active' ? 'Disable' : 'Enable' }}
                            </button>
                            <a href="{{ route('admin.webhooks.logs.index', ['webhookId' => $webhook->id]) }}" class="text-gray-600 hover:text-gray-900 mr-3">Logs</a>
                            <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            No webhooks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $webhooks->links() }}
    </div>
</div>