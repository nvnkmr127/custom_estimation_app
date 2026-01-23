<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Estimates</h1>
                <p class="mt-1 text-sm text-slate-500">Manage and track your estimates, status, and client proposals.
                </p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-2">
                @can('create', App\Models\Estimate::class)
                    <a href="{{ route('estimates.create') }}"
                        class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all duration-200">
                        Create Estimate
                    </a>
                @endcan
            </div>
        </div>

        <!-- Analytics Summary -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            <!-- Total -->
            <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
                class="block overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5 hover:bg-gray-50 transition-colors">
                <dt class="truncate text-sm font-medium text-gray-500">Total Estimates</dt>
                <dd class="mt-2 flex items-baseline text-2xl font-semibold text-gray-900">
                    {{ number_format($stats['total_count']) }}
                    <span
                        class="ml-2 text-sm font-normal text-gray-500">({{ number_format($stats['total_value'], 2) }})</span>
                </dd>
            </a>

            @php
                $statusColors = [
                    'draft' => 'text-gray-600',
                    'waiting_approval' => 'text-amber-600',
                    'approved' => 'text-blue-600',
                    'sent' => 'text-indigo-600',
                    'accepted' => 'text-green-600',
                    'declined' => 'text-rose-600',
                    'expired' => 'text-orange-600',
                ];
            @endphp

            @foreach(['draft', 'waiting_approval', 'approved', 'sent', 'accepted', 'declined'] as $status)
                <a href="{{ request()->fullUrlWithQuery(['status' => $status]) }}"
                    class="block overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5 hover:bg-gray-50 transition-colors">
                    <dt class="truncate text-sm font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $status)) }}
                    </dt>
                    <dd
                        class="mt-2 flex items-baseline text-2xl font-semibold {{ $statusColors[$status] ?? 'text-gray-900' }}">
                        {{ number_format($stats[$status . '_count'] ?? 0) }}
                        <span
                            class="ml-2 text-sm font-normal text-gray-500">({{ number_format($stats[$status . '_value'] ?? 0, 2) }})</span>
                    </dd>
                </a>
            @endforeach

            <!-- Conversion Rate -->
            <div class="overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
                <dt class="truncate text-sm font-medium text-gray-500">Conversion Rate</dt>
                <dd class="mt-2 flex items-baseline text-2xl font-semibold text-purple-600">
                    {{ $stats['conversion_rate'] }}%
                </dd>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-4" x-data="{ 
            showFilters: {{ request()->hasAny(['date_from', 'date_to', 'amount_min', 'amount_max', 'client_id', 'status']) ? 'true' : 'false' }},
            setDate(days) {
                const end = new Date();
                const start = new Date();
                start.setDate(end.getDate() - days);
                
                // Format YYYY-MM-DD
                const formatDate = (d) => d.toISOString().split('T')[0];
                
                document.getElementById('date_to').value = formatDate(end);
                document.getElementById('date_from').value = formatDate(start);
            }
        }">
            <form method="GET" action="{{ route('estimates.index') }}">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="block w-full rounded-md border-0 py-2 pl-10 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Search by estimate number or client...">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Search
                        </button>
                        <button type="button" @click="showFilters = !showFilters"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                class="w-4 h-4 text-gray-500">
                                <path fill-rule="evenodd"
                                    d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"></span>
                        </button>
                        <a href="{{ route('estimates.index') }}"
                            class="flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Clear</a>
                    </div>
                </div>

                <div x-show="showFilters" x-transition class="mt-4 border-t pt-4 border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-xs font-medium text-gray-700">Status</label>
                            <select name="status" id="status"
                                class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="all">All Statuses</option>
                                @foreach(['draft', 'waiting_approval', 'approved', 'sent', 'accepted', 'declined'] as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="client_id" class="block text-xs font-medium text-gray-700">Client</label>
                            <select name="client_id" id="client_id"
                                class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="">All Clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 flex justify-between">
                                <span>Date Range</span>
                                <div class="flex gap-2">
                                    <button type="button" @click="setDate(7)"
                                        class="text-[10px] text-indigo-600 hover:text-indigo-900 hover:underline">Last
                                        7d</button>
                                    <button type="button" @click="setDate(30)"
                                        class="text-[10px] text-indigo-600 hover:text-indigo-900 hover:underline">Last
                                        30d</button>
                                </div>
                            </label>
                            <div class="flex gap-2 mt-1">
                                <div class="relative w-full">
                                    <input type="date" name="date_from" id="date_from"
                                        value="{{ request('date_from') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <span class="text-gray-400 self-center">-</span>
                                <div class="relative w-full">
                                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4">
                        <!-- Amount Range -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700">Amount Range</label>
                            <div class="flex gap-2 mt-1">
                                <input type="number" name="amount_min" placeholder="Min"
                                    value="{{ request('amount_min') }}"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <span class="text-gray-400 self-center">-</span>
                                <input type="number" name="amount_max" placeholder="Max"
                                    value="{{ request('amount_max') }}"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                        </div>
                        <div class="sm:col-span-2 flex items-end">
                            <button type="submit"
                                class="w-full rounded-md bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-600 shadow-sm border border-indigo-200 hover:bg-indigo-100 hover:border-indigo-300 transition-colors">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Status Tabs (Preserved as quick filters) -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                @foreach(['all', 'draft', 'waiting_approval', 'approved', 'sent', 'accepted', 'declined'] as $status)
                    <a href="{{ route('estimates.index', array_merge(request()->except('status', 'page'), ['status' => $status])) }}"
                        class="{{ request('status', 'all') == $status ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium capitalize whitespace-nowrap">
                        {{ str_replace('_', ' ', $status) }}
                        <span
                            class="{{ request('status', 'all') == $status ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-900' }} ml-3 hidden rounded-full py-0.5 px-2.5 text-xs font-medium md:inline-block">
                            {{ $counts[$status] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flow-root" x-data="{ selected: [], allSelected: false }">

            <!-- Bulk Actions Toolbar -->
            <form id="bulkActionForm" method="POST" action="{{ route('estimates.bulk-update') }}">
                @csrf
                <div x-show="selected.length > 0" x-transition
                    class="mb-4 bg-indigo-50 rounded-lg p-3 flex items-center justify-between border border-indigo-100">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-indigo-700" x-text="selected.length + ' selected'"></span>
                        <!-- Hidden Inputs for logic -->
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="estimate_ids[]" :value="id">
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <select name="action"
                            class="block rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 text-xs">
                            <option value="">Choose Action...</option>
                            <option value="mark_sent">Mark as Sent</option>
                            <option value="mark_accepted">Mark as Accepted</option>
                            <option value="mark_declined">Mark as Declined</option>
                            <option value="mark_draft">Revert to Draft</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit"
                            class="rounded bg-indigo-600 px-2 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Apply</button>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="relative px-7 sm:w-12 sm:px-6 py-3.5">
                                    <input type="checkbox"
                                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                        @change="allSelected = !allSelected; if(allSelected) { selected = {{ json_encode($estimates->pluck('id')) }}; } else { selected = []; }"
                                        :checked="allSelected">
                                </th>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-900 sm:pl-0">
                                    Estimate</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Client
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Date
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Status
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Amount
                                </th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($estimates as $estimate)
                                <tr class="group hover:bg-gray-50 transition-colors">
                                    <td class="relative px-7 sm:w-12 sm:px-6">
                                        <input type="checkbox" value="{{ $estimate->id }}" :value="{{ $estimate->id }}"
                                            x-model="selected"
                                            class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                    </td>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-0">
                                        <a href="{{ route('estimates.show', $estimate) }}"
                                            class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                            Estimate #{{ $estimate->estimate_number }}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ ucfirst(str_replace('_', ' ', $estimate->type)) }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="font-medium text-gray-900">{{ $estimate->client?->name ?? 'No Client' }}
                                        </div>
                                        <div class="text-xs text-gray-400">Created by
                                            {{ $estimate->creator?->name ?? 'System' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div>{{ $estimate->estimate_date->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-400">{{ $estimate->estimate_date->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <x-estimate-status-badge :status="$estimate->status" />
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900">
                                        {{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <a href="{{ route('estimates.edit', $estimate) }}"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                        <form action="{{ route('estimates.destroy', $estimate) }}" method="POST"
                                            class="inline-block" onsubmit="return confirm('Delete this estimate?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-rose-600 hover:text-rose-900 bg-transparent border-0 p-0 cursor-pointer">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center">
                                        <x-empty-state title="No estimates found"
                                            description="Try adjusting your filters or create a new estimate."
                                            actionText="Create Estimate" actionLink="{{ route('estimates.create') }}"
                                            class="rounded-none shadow-none ring-0" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $estimates->links() }}
            </div>
        </div>
    </div>
</x-app-layout>