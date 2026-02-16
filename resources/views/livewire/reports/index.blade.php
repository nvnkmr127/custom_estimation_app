<div class="space-y-6">
    <!-- Filters -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Performance Report</h1>
            <p class="mt-2 text-sm text-slate-500">Analyze your business metrics over time.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <select wire:model.live="filter"
                class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="this_week">This Week</option>
                <option value="last_week">Last Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
            </select>
            @if(count($users) > 1)
                <select wire:model.live="userId"
                    class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            @endif
            <!-- Custom Date Range could be added here later -->
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Revenue Card -->
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
            <dt>
                <div class="absolute rounded-md bg-indigo-500 p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Revenue</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($currentRevenue, 2) }}</p>
                <p
                    class="ml-2 flex item-baseline text-sm font-semibold {{ $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if($revenueGrowth >= 0)
                        <svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-5 w-5 flex-shrink-0 self-center text-red-500" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                    <span class="sr-only"> {{ $revenueGrowth >= 0 ? 'Increased' : 'Decreased' }} by </span>
                    {{ abs($revenueGrowth) }}%
                </p>
            </dd>

            <!-- Revenue Goal Tracking -->
            <div class="px-4 pb-4">
                <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                    <span>Goal: {{ number_format($revenueTarget) }}</span>
                    <span>{{ $goalProgress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-200"
                    title="Goal: {{ number_format($revenueTarget) }}">
                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $goalProgress }}%"></div>
                </div>
                <input type="number" wire:model.blur="revenueTarget"
                    class="mt-2 block w-full rounded-md border-0 py-1 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6"
                    placeholder="Set Goal">
            </div>
        </div>

        <!-- Estimates Card -->
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
            <dt>
                <div class="absolute rounded-md bg-indigo-500 p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Estimates Sent</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">{{ $totalCount }}</p>
                <p
                    class="ml-2 flex item-baseline text-sm font-semibold {{ $estimatesGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if($estimatesGrowth >= 0)
                        <svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="h-5 w-5 flex-shrink-0 self-center text-red-500" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                    <span class="sr-only"> {{ $estimatesGrowth >= 0 ? 'Increased' : 'Decreased' }} by </span>
                    {{ abs($estimatesGrowth) }}%
                </p>
            </dd>
        </div>

        <!-- Win Rate Card -->
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
            <dt>
                <div class="absolute rounded-md bg-indigo-500 p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Win Rate</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">{{ $winRate }}%</p>
                <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                    <div class="text-sm">
                        <span class="font-medium text-gray-500">Vs Previous: {{ $prevWinRate }}%</span>
                    </div>
                </div>
            </dd>
        </div>

        <!-- Pipeline Card -->
        <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
            <dt>
                <div class="absolute rounded-md bg-indigo-500 p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Pipeline Value</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-7">
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($pipelineValue, 2) }}</p>
                <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                    <div class="text-sm">
                        <span class="text-gray-500">Pending Approvals & Drafts</span>
                    </div>
                </div>
            </dd>
        </div>
    </dl>

    <!-- Activity Metrics -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Avg. Days to Close</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $avgDaysToClose }} <span
                    class="text-sm font-normal text-gray-500">days</span></dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Avg. Deal Size</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($avgDealValue, 2) }}
            </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Downloads</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $downloadsCount }}</dd>
        </div>

        <!-- Second Row -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Avg. Discount (On Wins)</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 text-green-600">{{ $avgDiscountWon }}%
            </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Avg. Discount (On Lost)</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 text-red-600">{{ $avgDiscountLost }}%
            </dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Versions Created</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $versionsCreated }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Estimates Approved (Internal)</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $internalApproved }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Internal Rejections</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $rejectionsCount }}</dd>
        </div>
    </dl>

    <!-- Charts Section -->
    <div class="space-y-8">
        <!-- Revenue Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Revenue Trend</h3>
            <div class="relative h-80 w-full" x-data="{
                    chart: null,
                    chartData: @json($trendData),
                    init() {
                         this.renderChart(this.chartData);
                         Livewire.on('chartDataUpdated', (data) => {
                             // Data is passed as an object { trendData: ..., ... }
                             if (data.trendData) {
                                this.chartData = data.trendData;
                                this.renderChart(this.chartData);
                             }
                         });
                    },
                    renderChart(data) {
                        if (this.chart) {
                            this.chart.destroy();
                        }
                        const ctx = this.$refs.canvas.getContext('2d');
                        
                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.map(d => d.date),
                                datasets: [{
                                    label: 'Revenue',
                                    data: data.map(d => d.total),
                                    borderColor: '#4f46e5',
                                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                             options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    }
                 }" wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Funnel Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Sales Funnel</h3>
                <div class="relative h-64 w-full" x-data="{
                        chart: null,
                        funnelData: @json($funnelData),
                        init() {
                             this.renderChart(this.funnelData);
                             Livewire.on('chartDataUpdated', (data) => {
                                 if (data.funnelData) {
                                    this.funnelData = data.funnelData;
                                    this.renderChart(this.funnelData);
                                 }
                             });
                        },
                        renderChart(funnel) {
                            if (this.chart) {
                                this.chart.destroy();
                            }
                            const ctx = this.$refs.canvas.getContext('2d');
                            
                            this.chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: ['Sent', 'Opened', 'Viewed', 'Accepted'],
                                    datasets: [{
                                        label: 'Count',
                                        data: [funnel.sent, funnel.opened, funnel.viewed, funnel.accepted],
                                        backgroundColor: [
                                            '#3b82f6', // blue-500
                                            '#6366f1', // indigo-500
                                            '#8b5cf6', // violet-500
                                            '#22c55e'  // green-500
                                        ],
                                        barPercentage: 0.6,
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        x: { beginAtZero: true }
                                    }
                                }
                            });
                        }
                    }" wire:ignore>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Donut Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Estimate Status Breakdown</h3>
                <div class="relative h-64 w-full flex justify-center" x-data="{
                        chart: null,
                        statusData: @json($statusData),
                        init() {
                             this.renderChart(this.statusData);
                             Livewire.on('chartDataUpdated', (data) => {
                                 if (data.statusData) {
                                    this.statusData = data.statusData;
                                    this.renderChart(this.statusData);
                                 }
                             });
                        },
                        renderChart(statusCounts) {
                            if (this.chart) {
                                this.chart.destroy();
                            }
                            const ctx = this.$refs.canvas.getContext('2d');
                            
                            // Default colors mapping
                            const colors = {
                                'accepted': '#22c55e', // green-500
                                'declined': '#ef4444', // red-500
                                'sent': '#3b82f6',     // blue-500
                                'draft': '#9ca3af',    // gray-400
                                'expired': '#f59e0b',  // amber-500
                                'waiting_approval': '#a855f7' // purple-500
                            };
    
                            const labels = Object.keys(statusCounts);
                            const data = Object.values(statusCounts);
                            const backgroundColors = labels.map(l => colors[l] || '#cbd5e1');
    
                            this.chart = new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1).replace('_', ' ')),
                                    datasets: [{
                                        data: data,
                                        backgroundColor: backgroundColors,
                                        borderWidth: 0
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { position: 'right' }
                                    }
                                }
                            });
                        }
                    }" wire:ignore>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Estimates -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Estimates</h3>
                <a href="{{ route('estimates.index') }}"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentEstimates as $estimate)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ $estimate->client->name ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $estimate->created_at->format('M d, Y') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                                                                                                                                                                                                                                            {{ match ($estimate->status) {
                                'accepted' => 'bg-green-50 text-green-700 ring-green-600/20',
                                'declined' => 'bg-red-50 text-red-700 ring-red-600/20',
                                'sent' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                default => 'bg-gray-50 text-gray-600 ring-gray-500/10'
                            } }}">
                                                            {{ ucfirst(str_replace('_', ' ', $estimate->status)) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                        {{ number_format($estimate->grand_total, 2) }}
                                                    </td>
                                                </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No recent estimates
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Top Performing Products</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sales Count</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topProducts as $product)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $product->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    {{ $product->count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    {{ number_format($product->revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No product data
                                    available in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hot Leads & Stale Pipeline Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8 mb-8">
        <!-- Hot Leads Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-orange-50">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-orange-800">🔥 Hot Leads</h3>
                    <p class="text-xs text-orange-600">High engagement, not yet accepted.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Views</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($hotLeads as $lead)
                            <tr class="hover:bg-orange-50/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ Str::limit($lead->client->name ?? 'Unknown', 15) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-bold text-orange-600">
                                    {{ $lead->view_count }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('estimates.edit', $lead->id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 text-xs uppercase font-bold">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-sm text-gray-500">No hot leads.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stale Pipeline Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-gray-700">❄️ Stale Pipeline</h3>
                    <p class="text-xs text-gray-500">Untouched for >14 days.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Days Idle</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($staleEstimates as $estimate)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ Str::limit($estimate->client->name ?? 'Unknown', 15) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-500">
                                    {{ $estimate->updated_at->diffInDays() }} days</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('estimates.edit', $estimate->id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 text-xs uppercase font-bold">Nudge</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-sm text-gray-500">Pipeline is fresh!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Clients Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden mt-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Top Viewed Clients</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client</th>
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estimates Sent</th>
                        <th scope="col"
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Views</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($topClients as $clientStat)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $clientStat->client->name ?? 'Unknown Client' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                {{ $clientStat->estimates_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                {{ $clientStat->total_views }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No client data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</div>