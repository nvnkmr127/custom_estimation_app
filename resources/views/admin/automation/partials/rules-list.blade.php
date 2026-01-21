<div class="mt-8" x-show="viewMode === 'list'">
    <div class="sm:flex sm:items-center sm:justify-between">
        <h2 class="text-lg font-medium leading-6 text-slate-900">Automation Rules</h2>
        <div class="flex gap-3">
            <button @click="openTemplateModal()" type="button"
                class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-indigo-300 hover:bg-slate-50">
                New from Template
            </button>
            <button
                @click="showCreateModal = true; editingRule = { id: null, name: '', trigger_event: '', condition_logic: 'AND', conditions: [], actions: [{type: 'email', to: '', field: '', value: '', delay: 0, conditions: []}], settings: { is_enabled: true, rate_limit_count: null, rate_limit_period: 1440, max_executions_per_entity: null }, is_active: true }"
                type="button"
                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create New Rule
            </button>
        </div>
    </div>

    <!-- Search, Filters, and Bulk Operations -->
    <div class="mt-6 space-y-4">
        <!-- Search and Filters Row -->
        <div class="flex flex-col sm:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <label for="search" class="sr-only">Search automations</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input x-model="searchQuery" type="search" id="search"
                        class="block w-full rounded-md border-0 py-2 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="Search by name or description...">
                </div>
            </div>

            <!-- Trigger Filter -->
            <div class="sm:w-48">
                <label for="trigger-filter" class="sr-only">Filter by trigger</label>
                <select x-model="filterTrigger" id="trigger-filter"
                    class="block w-full rounded-md border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">All Triggers</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}">{{ $event }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="sm:w-40">
                <label for="status-filter" class="sr-only">Filter by status</label>
                <select x-model="filterStatus" id="status-filter"
                    class="block w-full rounded-md border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <button @click="searchQuery = ''; filterTrigger = ''; filterStatus = ''"
                x-show="searchQuery || filterTrigger || filterStatus" type="button"
                class="inline-flex items-center px-3 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Clear
            </button>
        </div>

        <!-- Bulk Operations Toolbar -->
        <div x-show="selectedRules.length > 0" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-indigo-900" x-text="selectedRules.length + ' selected'"></span>
                <button @click="selectedRules = []" type="button" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Deselect all
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button @click="bulkEnable()" type="button"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    Enable
                </button>
                <button @click="bulkDisable()" type="button"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-slate-600 hover:bg-slate-700">
                    Disable
                </button>
                <button @click="bulkDelete()" type="button"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <div class="mt-8 flow-root">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="relative px-7 sm:w-12 sm:px-6">
                                <input type="checkbox" @change="toggleSelectAll($event.target.checked)"
                                    :checked="selectedRules.length === {{ $rules->count() }} && {{ $rules->count() }} > 0"
                                    class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                            </th>
                            <th scope="col"
                                class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-900 sm:pl-6">
                                Workflow / Version
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-900">
                                Trigger Event</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-900">
                                Channels / Steps</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-900">
                                Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-6 sm:pr-6">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($rules as $rule)
                            <tr class="hover:bg-slate-50 transition-colors"
                                :class="selectedRules.includes({{ $rule->id }}) ? 'bg-indigo-50' : ''">
                                <td class="relative px-7 sm:w-12 sm:px-6">
                                    <input type="checkbox" :value="{{ $rule->id }}"
                                        @change="toggleSelectRule({{ $rule->id }})"
                                        :checked="selectedRules.includes({{ $rule->id }})"
                                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                </td>
                                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                    <div class="flex flex-col">
                                        <span>{{ $rule->name }}</span>
                                        <span
                                            class="text-[10px] text-slate-400 font-normal italic">v{{ $rule->version }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    <span
                                        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $rule->triggers->first()->event_name ?? 'N/A' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    <div class="flex gap-1 flex-wrap max-w-xs">
                                        @foreach ($rule->steps as $step)
                                            @php
                                                $channelColors = [
                                                    'email' => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10',
                                                    'webhook' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
                                                    'notification' => 'bg-amber-50 text-amber-700 ring-amber-700/10',
                                                    'status_update' =>
                                                        'bg-slate-50 text-slate-700 ring-slate-700/10',
                                                ];
                                                $colorClass =
                                                    $channelColors[$step->action->type ?? ''] ??
                                                    'bg-purple-50 text-purple-700 ring-purple-700/10';
                                            @endphp
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1 text-[10px] font-medium ring-1 ring-inset {{ $colorClass }}">
                                                {{ strtoupper($step->action->type ?? 'Step') }}
                                                @if ($step->delay > 0)
                                                    <span class="ml-1 opacity-60">+{{ $step->delay }}s</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                    @if ($rule->is_active)
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">Inactive</span>
                                    @endif
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium sm:pr-6">
                                    <div
                                        class="flex justify-end gap-2 items-center opacity-80 hover:opacity-100 transition-opacity">
                                        <button @click="viewVisualization({{ $rule->id }})"
                                            class="text-purple-600 hover:text-purple-900 transition-colors"
                                            title="View Workflow">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </button>
                                        <button @click="viewMetrics({{ $rule->id }})"
                                            class="text-emerald-600 hover:text-emerald-900 transition-colors"
                                            title="Performance Metrics">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <button @click="viewLogs({{ $rule->id }})"
                                            class="text-slate-600 hover:text-slate-900 transition-colors"
                                            title="Execution Logs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('automation.duplicate', $rule) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="text-cyan-600 hover:text-cyan-900 transition-colors"
                                                title="Duplicate">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('automation.version', $rule) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="text-blue-600 hover:text-blue-900 transition-colors"
                                                title="Clone as New Version">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V7a2 2 0 01-2 2h-2M3 18v-6a2 2 0 012-2h11" />
                                                </svg>
                                            </button>
                                        </form>
                                        <button @click='editRule(@json($rule))'
                                            class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('automation.destroy', $rule) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure? This will delete this version permanently.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-rose-600 hover:text-rose-900 transition-colors" title="Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div
                                            class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-2 text-sm font-semibold text-slate-900">No automation rules
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-500">Get started by creating a new
                                            workflow.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Pagination -->
    <div class="mt-4">
        {{ $rules->links() }}
    </div>
</div>