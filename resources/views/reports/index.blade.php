<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Business Reports</h1>
            <p class="mt-2 text-sm text-slate-500">Key metrics and performance indicators.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Win Rate</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $winRate }}%</dd>
            <div class="mt-2 text-xs text-secondary-500">Accepted vs Declined</div>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Total Revenue</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($totalRevenue, 2) }}
            </dd>
            <div class="mt-2 text-xs text-green-600">Realized Income</div>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Pipeline Value</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($pipelineValue, 2) }}
            </dd>
            <div class="mt-2 text-xs text-blue-600">Pending & Drafts</div>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Estimates Sent</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $totalEstimates }}</dd>
            <div class="mt-2 text-xs text-gray-500">{{ $acceptedEstimates }} Accepted</div>
        </div>
        <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <dt class="truncate text-sm font-medium text-gray-500">Revenue Forecast</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight text-indigo-600">
                {{ number_format($weightedForecast, 2) }}</dd>
            <div class="mt-2 text-xs text-gray-500">70% Weighted Pipeline</div>
        </div>
    </div>

    <!-- Funnel Analysis -->
    <div class="mb-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Conversion Funnel</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div class="text-center">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $funnel['sent'] }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Opened</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $funnel['opened'] }}</div>
                    <div class="mt-1 text-xs text-indigo-600">{{ $funnel['rates']['open_rate'] }}% Open Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Viewed</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $funnel['viewed'] }}</div>
                    <div class="mt-1 text-xs text-indigo-600">{{ $funnel['rates']['view_rate'] }}% View Rate</div>
                </div>
                <div class="text-center">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Accepted</div>
                    <div class="mt-1 text-2xl font-bold text-green-600">{{ $funnel['accepted'] }}</div>
                    <div class="mt-1 text-xs text-green-600">{{ $funnel['rates']['conversion_rate'] }}% Conversion</div>
                </div>
            </div>

            <!-- Funnel Visualization Placeholder (Simple CSS bar) -->
            <div class="mt-8 relative h-4 w-full bg-gray-100 rounded-full overflow-hidden flex">
                <div class="h-full bg-indigo-200" style="width: 100%"></div>
                <div class="h-full bg-indigo-400" style="width: {{ $funnel['rates']['open_rate'] }}%"></div>
                <div class="h-full bg-indigo-600"
                    style="width: {{ $funnel['rates']['open_rate'] * ($funnel['rates']['view_rate'] / 100) }}%"></div>
                <div class="h-full bg-green-500"
                    style="width: {{ $funnel['rates']['open_rate'] * ($funnel['rates']['view_rate'] / 100) * ($funnel['rates']['conversion_rate'] / 100) }}%">
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Monthly Revenue -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Monthly Revenue (Last 6 Months)</h3>
            </div>
            <div class="px-6 py-5 relative">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('revenueChart');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                            label: 'Revenue',
                            data: @json($chartData),
                            backgroundColor: '#6366f1',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            </script>
        @endpush
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Top Products by Revenue</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units
                        Sold</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Revenue</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($topProducts as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                            {{ number_format($product->revenue, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>