<div x-show="viewMode === 'analytics'" class="mt-8" style="display: none;">
    <div x-show="loadingAnalytics" class="flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
    </div>

    <div x-show="!loadingAnalytics && dashboardStats">
        <!-- Summary Cards -->
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6">
                <dt>
                    <div class="absolute rounded-md bg-indigo-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Total Executions (30 Days)</p>
                </dt>
                <dd class="ml-16 flex items-baseline pb-1 sm:pb-7">
                    <p class="text-2xl font-semibold text-slate-900" x-text="dashboardStats?.summary?.total_runs || 0">
                    </p>
                </dd>
            </div>

            <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6">
                <dt>
                    <div class="absolute rounded-md bg-green-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="ml-16 truncate text-sm font-medium text-slate-500">Avg Success Rate</p>
                </dt>
                <dd class="ml-16 flex items-baseline pb-1 sm:pb-7">
                    <p class="text-2xl font-semibold text-slate-900"
                        x-text="(dashboardStats?.summary?.avg_success_rate || 0) + '%'"></p>
                </dd>
            </div>
        </dl>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Trend Chart -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium leading-6 text-slate-900 mb-4">Execution Trend</h3>
                <div class="relative h-72 w-full">
                    <canvas id="analyticsChart"></canvas>
                </div>
            </div>

            <!-- Top Automations -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium leading-6 text-slate-900 mb-4">Top Active Rules</h3>
                <template x-if="dashboardStats">
                    <ul role="list" class="divide-y divide-slate-200">
                        <template x-for="rule in dashboardStats.top_automations" :key="rule.name">
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-900" x-text="rule.name">
                                        </p>
                                        <p class="truncate text-xs text-slate-500">
                                            <span class="text-green-600" x-text="rule.success + ' success'"></span>
                                            •
                                            <span class="text-red-600" x-text="rule.failed + ' failed'"></span>
                                        </p>
                                    </div>
                                    <div class="inline-flex items-center text-base font-semibold text-slate-900"
                                        x-text="rule.total">
                                    </div>
                                </div>
                            </li>
                        </template>
                        <template x-if="dashboardStats.top_automations.length === 0">
                            <li class="py-4 text-sm text-slate-500 text-center">No data available</li>
                        </template>
                    </ul>
                </template>
            </div>
        </div>
    </div>
</div>