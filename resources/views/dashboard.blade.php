<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-2 text-sm text-slate-500">Overview of your estimation business.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('estimates.create') }}"
                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Create Estimate
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Revenue Forecast</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-indigo-600">
                <span
                    class="text-lg font-medium mr-1">{{ \App\Models\Setting::getCurrencySymbol() }}</span>{{ number_format($weightedForecast, 2) }}
            </dd>
            <dd class="mt-1 text-xs text-gray-500">70% Weighted Pipeline</dd>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Pipeline Value</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                <span
                    class="text-lg font-medium mr-1">{{ \App\Models\Setting::getCurrencySymbol() }}</span>{{ number_format($pipeline_revenue, 2) }}
            </dd>
            <dd class="mt-1 text-xs text-gray-500">{{ $stats['sent'] }} Sent, {{ $stats['draft'] }} Draft</dd>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Converted Revenue</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-green-600">
                <span
                    class="text-lg font-medium mr-1">{{ \App\Models\Setting::getCurrencySymbol() }}</span>{{ number_format($converted_revenue, 2) }}
            </dd>
            <dd class="mt-1 text-xs text-green-600">{{ $stats['accepted'] }} Accepted</dd>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
        <!-- Overview Stats -->
        <div class="grid grid-cols-2 gap-4">
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <dt class="truncate text-sm font-medium text-gray-500">Total Estimates</dt>
                <dd class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">{{ $stats['total'] }}</dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <dt class="truncate text-sm font-medium text-gray-500">Win Rate</dt>
                <dd class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">{{ round($conversion_rate, 1) }}%
                </dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <dt class="truncate text-sm font-medium text-gray-500">Pending Tasks</dt>
                <dd class="mt-2 text-2xl font-semibold tracking-tight text-amber-600">{{ $stats['tasks_pending'] }}</dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <dt class="truncate text-sm font-medium text-gray-500">Overdue Tasks</dt>
                <dd class="mt-2 text-2xl font-semibold tracking-tight text-red-600">{{ $stats['tasks_overdue'] }}</dd>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <!-- Hot Leads Widget -->
            @if(isset($hot_leads) && $hot_leads->count() > 0)
                <div class="bg-white shadow-sm ring-1 ring-red-100 sm:rounded-xl overflow-hidden">
                    <div class="px-4 py-4 sm:px-6 border-b border-red-50 bg-red-50/30 flex justify-between items-center">
                        <h3 class="text-sm font-semibold leading-6 text-red-700 flex items-center gap-2">
                            <svg class="h-5 w-5 text-red-500 animate-pulse" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M13.5 4.938a7 7 0 11-9.006 1.737c.202-.257.59-.218.793.08a5.002 5.002 0 002.502 8.01 5 5 0 005.15-1.928 2.5 2.5 0 013.9 1.156c.196.883-.559 1.543-1.42 1.492a2.503 2.503 0 01-1.72-.756.75.75 0 00-1.06 1.06 4.003 4.003 0 005.418-5.32c-.066-.192-.28-.307-.478-.266a2.5 2.5 0 01-2.91-4.225.75.75 0 00-.916-1.04z"
                                    clip-rule="evenodd" />
                            </svg>
                            Hot Leads
                        </h3>
                        <span
                            class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">{{ $hot_leads->count() }}
                            Alert</span>
                    </div>
                    <ul role="list" class="divide-y divide-red-50">
                        @foreach($hot_leads as $lead)
                            <li
                                class="relative flex justify-between gap-x-6 px-4 py-4 hover:bg-red-50/20 sm:px-6 transition-colors">
                                <div class="flex min-w-0 gap-x-4">
                                    <div class="min-w-0 flex-auto">
                                        <p class="text-xs font-semibold leading-6 text-gray-900">
                                            <a href="{{ route('estimates.show', $lead) }}">
                                                <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                                {{ $lead->client->name ?? 'Unknown Client' }}
                                                <span class="text-gray-400 font-normal">#{{ $lead->estimate_number }}</span>
                                            </a>
                                        </p>
                                        <div class="mt-1 flex items-center gap-x-2 text-[10px] text-gray-500">
                                            <span>Score: {{ $lead->engagement_score }}</span>
                                            <span class="text-gray-300">&bull;</span>
                                            <span class="text-red-600 font-medium">Last viewed
                                                {{ $lead->last_viewed_at ? $lead->last_viewed_at->diffForHumans() : 'Recently' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-x-4">
                                    <div class="text-right">
                                        <p class="text-xs font-medium text-gray-900">
                                            {{ \App\Models\Setting::getCurrencySymbol() }}
                                            {{ number_format($lead->grand_total, 2) }}
                                        </p>
                                        <span
                                            class="inline-flex items-center rounded-md bg-white px-1.5 py-0.5 text-[10px] font-medium text-gray-600 ring-1 ring-inset ring-gray-200">View</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Recent Estimates -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                <div class="px-4 py-4 sm:px-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-sm font-semibold leading-6 text-gray-900">Recent Estimates</h3>
                </div>
                <ul role="list" class="divide-y divide-gray-100">
                    @forelse($recent_estimates as $estimate)
                        <li class="relative flex justify-between gap-x-6 px-4 py-4 hover:bg-gray-50 sm:px-6">
                            <div class="flex min-w-0 gap-x-4">
                                <div class="min-w-0 flex-auto">
                                    <p class="text-xs font-semibold leading-6 text-gray-900">
                                        <a href="{{ route('estimates.show', $estimate) }}">
                                            <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                            {{ $estimate->title }}
                                        </a>
                                    </p>
                                    <p class="mt-1 text-[10px] text-gray-500">#{{ $estimate->estimate_number }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-x-4">
                                <div class="text-right">
                                    <p class="text-xs font-medium text-gray-900">
                                        {{ \App\Models\Setting::getCurrencySymbol() }}
                                        {{ number_format($estimate->grand_total, 2) }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-gray-500">{{ ucfirst($estimate->status) }}</p>
                                </div>
                                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-5 text-center text-xs text-gray-500 italic">No recent estimates.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- Recent Tasks -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-semibold leading-6 text-gray-900">Assigned Tasks</h3>
            </div>
            <ul role="list" class="divide-y divide-gray-100">
                @forelse($recent_tasks as $task)
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-gray-900">{{ $task->title }}</span>
                                <span class="text-[10px] text-gray-500 mt-1">Due:
                                    {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No date' }}</span>
                            </div>
                            <span
                                class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $task->is_completed ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' }}">
                                {{ $task->is_completed ? 'Completed' : 'Pending' }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-4 text-center text-xs text-gray-500 italic">No tasks found.</li>
                @endforelse
            </ul>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-semibold leading-6 text-gray-900">System Activity</h3>
            </div>
            <div class="flow-root px-4 py-4 sm:px-6">
                <ul role="list" class="-mb-8">
                    @forelse($recent_activities as $activity)
                        <li>
                            <div class="relative pb-8">
                                @if (!$loop->last)
                                    <span class="absolute left-2 top-2 -ml-px h-full w-0.5 bg-gray-200"
                                        aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span
                                            class="h-4 w-4 bg-indigo-500 rounded-full flex items-center justify-center ring-4 ring-white">
                                            <div class="h-1.5 w-1.5 bg-white rounded-full"></div>
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-0">
                                        <div>
                                            <p class="text-xs text-gray-500">{{ $activity->description }} <span
                                                    class="font-medium text-gray-900">{{ $activity->user->name ?? 'System' }}</span>
                                            </p>
                                        </div>
                                        <div class="whitespace-nowrap text-right text-[10px] text-gray-500">
                                            <time
                                                datetime="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-center text-xs text-gray-500 italic py-4">No recent activity.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>