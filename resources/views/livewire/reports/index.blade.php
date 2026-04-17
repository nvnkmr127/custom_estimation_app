<div class="space-y-6" x-data="{ activeTab: @entangle('activeTab') }" x-init="
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        document.head.appendChild(script);
    }
">
    <!-- Header & Global Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-white rounded-xl shadow-sm p-6 border border-slate-100">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Performance Intelligence</h1>
            <p class="mt-1 text-sm text-slate-500">Business-critical metrics and growth indicators.</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center space-x-3">
            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="inline-flex items-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all">
                <x-heroicon-o-arrow-down-tray class="mr-2 h-4 w-4" />
                Export CSV
                <span wire:loading wire:target="exportCsv" class="ml-2">...</span>
            </button>
            <div class="h-8 w-px bg-slate-200 mx-2"></div>
            <select wire:model.live="filter"
                class="rounded-lg border-slate-200 py-2 pl-3 pr-10 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="this_week">This Week</option>
                <option value="last_week">Last Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
            </select>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">User</label>
            <select wire:model.live="userId" class="w-full rounded-lg border-slate-200 text-sm py-2">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Client</label>
            <select wire:model.live="clientId" class="w-full rounded-lg border-slate-200 text-sm py-2">
                <option value="">All Clients</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</label>
            <select wire:model.live="status" class="w-full rounded-lg border-slate-200 text-sm py-2">
                <option value="all">Any Status</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="accepted">Accepted</option>
                <option value="declined">Declined</option>
            </select>
        </div>
        <div class="flex space-x-2">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">From</label>
                <input type="date" wire:model.live="customStartDate" class="w-full rounded-lg border-slate-200 text-sm py-2">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">To</label>
                <input type="date" wire:model.live="customEndDate" class="w-full rounded-lg border-slate-200 text-sm py-2">
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-white/50 backdrop-blur-sm">
        <div class="flex flex-col items-center">
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"></div>
            <span class="mt-2 text-sm font-semibold text-slate-700">Calculating metrics...</span>
        </div>
    </div>

    <!-- Overview Metrics (Bento Grid) -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Revenue Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-all cursor-pointer"
             onclick="window.location.href='{{ route('estimates.index', ['client_status' => 'accepted']) }}'">
            <div class="flex items-center justify-between">
                <div class="rounded-lg bg-emerald-50 p-2 text-emerald-600 group-hover:bg-emerald-100 transition-colors">
                    <x-heroicon-o-currency-dollar class="h-6 w-6" />
                </div>
                <div class="flex items-center space-x-1 {{ $revenue['growth'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    <span class="text-xs font-bold">{{ $revenue['growth'] >= 0 ? '+' : '' }}{{ $revenue['growth'] }}%</span>
                    @if($revenue['growth'] >= 0)
                        <x-heroicon-o-arrow-trending-up class="h-4 w-4" />
                    @else
                        <x-heroicon-o-arrow-trending-down class="h-4 w-4" />
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Revenue</h3>
                <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($revenue['total'], 2) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $revenue['count'] }} accepted estimates</p>
            </div>
            <!-- Progress towards goal -->
            <div class="mt-4">
                <div class="flex justify-between text-[10px] font-bold text-slate-400 mb-1">
                    <span>GOAL PROGRESS</span>
                    <span>{{ $goalProgress }}%</span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $goalProgress }}%"></div>
                </div>
            </div>
        </div>

        <!-- Pipeline Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-all cursor-pointer"
             onclick="window.location.href='{{ route('estimates.index', ['status' => 'open']) }}'">
            <div class="flex items-center justify-between">
                <div class="rounded-lg bg-indigo-50 p-2 text-indigo-600 group-hover:bg-indigo-100 transition-colors">
                    <x-heroicon-o-funnel class="h-6 w-6" />
                </div>
                <span class="text-xs font-bold text-indigo-600">Active Pipeline</span>
            </div>
            <div class="mt-4">
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Pipeline Value</h3>
                <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($pipeline['total'], 2) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $pipeline['count'] }} estimates in flow</p>
            </div>
        </div>

        <!-- Conversion Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="rounded-lg bg-blue-50 p-2 text-blue-600 group-hover:bg-blue-100 transition-colors">
                    <x-heroicon-o-check-badge class="h-6 w-6" />
                </div>
                <span class="text-xs font-bold text-blue-600">Win Rate</span>
            </div>
            <div class="mt-4 text-center">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="h-20 w-20">
                        <circle class="text-slate-100" stroke-width="6" stroke="currentColor" fill="transparent" r="32" cx="40" cy="40"/>
                        <circle class="text-blue-500" stroke-width="6" stroke-dasharray="{{ ($conversion['rate'] / 100) * 201 }} 201" stroke-linecap="round" stroke="currentColor" fill="transparent" r="32" cx="40" cy="40"/>
                    </svg>
                    <span class="absolute text-lg font-bold text-slate-900">{{ $conversion['rate'] }}%</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">{{ $conversion['accepted'] }} won / {{ $conversion['sent'] }} sent</p>
            </div>
        </div>

        <!-- Approval Card -->
        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="rounded-lg bg-amber-50 p-2 text-amber-600 group-hover:bg-amber-100 transition-colors">
                    <x-heroicon-o-shield-check class="h-6 w-6" />
                </div>
                <span class="text-xs font-bold text-amber-600">Approval Flow</span>
            </div>
            <div class="mt-4">
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wider">Approval Rate</h3>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $approval['rate'] }}%</p>
                <p class="text-xs text-slate-400 mt-1">{{ $approval['approved'] }} approved / {{ $approval['pending'] }} pending</p>
            </div>
        </div>
    </div>

    <!-- Tabs for Detailed Reports -->
    <div class="mt-8">
        <div class="sm:hidden">
            <select wire:model.live="activeTab" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="summary">Summary</option>
                <option value="funnel">Sales Funnel</option>
                <option value="performance">Leaderboard</option>
                <option value="discounts">Discount Analysis</option>
            </select>
        </div>
        <div class="hidden sm:block">
            <nav class="flex space-x-4 border-b border-slate-200" aria-label="Tabs">
                <button wire:click="$set('activeTab', 'summary')" 
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-all {{ $activeTab === 'summary' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    Revenue Trends
                </button>
                <button wire:click="$set('activeTab', 'funnel')" 
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-all {{ $activeTab === 'funnel' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    Conversion Funnel
                </button>
                <button wire:click="$set('activeTab', 'performance')" 
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-all {{ $activeTab === 'performance' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    Sales Performance
                </button>
                <button wire:click="$set('activeTab', 'discounts')" 
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-all {{ $activeTab === 'discounts' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    Discount Impact
                </button>
            </nav>
        </div>
    </div>

    <!-- Active Report Content -->
    <div class="mt-6">
        @if($activeTab === 'summary')
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-900">Revenue Trajectory</h3>
                    <div class="flex items-center space-x-2 text-xs text-slate-400">
                        <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                        <span>Accepted Revenue</span>
                    </div>
                </div>
                <div class="h-80 w-full" x-data="{
                    chart: null,
                    init() {
                        this.renderChart(@js($revenueTrend));
                        Livewire.on('chart-updated', (data) => this.renderChart(data.revenueTrend));
                    },
                    renderChart(data) {
                        if (this.chart) this.chart.destroy();
                        if (!data || data.length === 0) return;
                        
                        const ctx = this.$refs.canvas.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.map(d => d.date),
                                datasets: [{
                                    label: 'Revenue',
                                    data: data.map(d => d.total),
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 3,
                                    pointBackgroundColor: '#6366f1',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { 
                                        beginAtZero: true,
                                        grid: { color: '#f8fafc' },
                                        ticks: { callback: (val) => '$' + val.toLocaleString() }
                                    },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }
                }" wire:ignore>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        @endif

        @if($activeTab === 'funnel')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-6">Visual Conversion Funnel</h3>
                    <div class="space-y-4">
                        @foreach($funnel as $index => $step)
                            @php
                                $percentage = $funnel[0]['count'] > 0 ? ($step['count'] / $funnel[0]['count']) * 100 : 0;
                                $width = max(30, $percentage); // Minimum width for visual clarity
                            @endphp
                            <div class="relative">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-bold text-slate-700">{{ $step['stage'] }}</span>
                                    <span class="text-sm font-bold text-indigo-600">{{ number_format($step['count']) }}</span>
                                </div>
                                <div class="h-10 w-full bg-slate-50 flex justify-center items-center rounded-lg overflow-hidden relative">
                                    <div class="absolute h-full transition-all duration-500 {{ match($index) { 0=>'bg-indigo-500', 1=>'bg-indigo-400', 2=>'bg-indigo-300', default=>'bg-emerald-500' } }}"
                                         style="width: {{ $width }}%"></div>
                                    <span class="relative z-10 text-[10px] font-black {{ $width > 50 ? 'text-white' : 'text-slate-600' }}">
                                        {{ $index === 0 ? 'SOURCE' : round($percentage) . '%' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-slate-900 rounded-2xl p-8 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <x-heroicon-o-light-bulb class="h-32 w-32" />
                    </div>
                    <h4 class="text-indigo-400 text-xs font-black uppercase tracking-widest mb-2">AI Insigths</h4>
                    <p class="text-lg font-medium leading-relaxed">
                        @if($conversion['rate'] < 20)
                            Your conversion rate is currently below benchmark. Focus on reducing "Sent" to "Viewed" friction by personalizing cover letters.
                        @else
                            Outstanding conversion! You are performing 15% better than last period. Consider raising your price floor for high-demand services.
                        @endif
                    </p>
                    <div class="mt-8 grid grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-xl p-4">
                            <span class="block text-xs text-indigo-300 font-bold">AVG. DEAL SIZE</span>
                            <span class="text-xl font-bold font-mono mt-1">${{ number_format($revenue['total'] / max(1, $revenue['count']), 0) }}</span>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <span class="block text-xs text-emerald-300 font-bold">TOTAL WINS</span>
                            <span class="text-xl font-bold font-mono mt-1">{{ $revenue['count'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'performance')
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-900">Top Estimators</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50">
                                <th class="px-6 py-4">Estimator</th>
                                <th class="px-6 py-4 text-center">Estimates</th>
                                <th class="px-6 py-4 text-center">Wins</th>
                                <th class="px-6 py-4 text-center">Win Rate</th>
                                <th class="px-6 py-4 text-right">Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($performance as $row)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-900">{{ $row['name'] }}</td>
                                    <td class="px-6 py-4 text-center text-slate-600 font-mono">{{ $row['total_estimates'] }}</td>
                                    <td class="px-6 py-4 text-center text-emerald-600 font-bold font-mono">{{ $row['won_count'] }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <div class="h-1.5 w-12 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-blue-500" style="width: {{ $row['win_rate'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-slate-600">{{ $row['win_rate'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-slate-900 font-mono">${{ number_format($row['revenue'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No activity recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($activeTab === 'discounts')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex flex-col items-center justify-center">
                    <div class="rounded-2xl bg-emerald-50 p-4 text-emerald-600 mb-4 ring-1 ring-emerald-100">
                        <x-heroicon-o-gift class="h-10 w-10" />
                    </div>
                    <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Avg. Discount (Accepted)</h4>
                    <p class="text-5xl font-black text-emerald-600 mt-2">{{ number_format($discountAnalysis['accepted'] ?? 0, 1) }}%</p>
                    <p class="text-xs text-slate-400 mt-4 max-w-xs text-center">Higher discounts on accepted deals might indicate a highly price-sensitive market or effective closing incentives.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex flex-col items-center justify-center">
                    <div class="rounded-2xl bg-rose-50 p-4 text-rose-600 mb-4 ring-1 ring-rose-100">
                        <x-heroicon-o-no-symbol class="h-10 w-10" />
                    </div>
                    <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Avg. Discount (Declined)</h4>
                    <p class="text-5xl font-black text-rose-600 mt-2">{{ number_format($discountAnalysis['declined'] ?? 0, 1) }}%</p>
                    <p class="text-xs text-slate-400 mt-4 max-w-xs text-center">High discounts on declined deals suggest that price alone is not the primary factor for losing these bids.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Recent Estimates Drill-down -->
    <div class="mt-12 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-900">Drilldown: Filtered Estimates</h3>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-400">Viewing {{ $recentEstimates->total() }} results</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50">
                        <th class="px-6 py-4">Estimate #</th>
                        <th class="px-6 py-4">Client</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Value</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentEstimates as $estimate)
                        <tr class="hover:bg-slate-50/80 transition-all group">
                            <td class="px-6 py-4 text-sm font-mono text-slate-500 group-hover:text-indigo-600">{{ $estimate->estimate_number }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $estimate->client->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $estimate->creator->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $estimate->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-widest ring-1 ring-inset {{ match($estimate->estimate_status) {
                                    'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'sent' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    'draft' => 'bg-slate-50 text-slate-600 ring-slate-500/20',
                                    default => 'bg-slate-50 text-slate-500'
                                } }}">
                                    {{ $estimate->estimate_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-black text-slate-900 font-mono">${{ number_format($estimate->grand_total, 2) }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('estimates.edit', $estimate->id) }}" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                    <x-heroicon-o-chevron-right class="h-5 w-5" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">No estimates match your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50">
            {{ $recentEstimates->links() }}
        </div>
    </div>
</div>