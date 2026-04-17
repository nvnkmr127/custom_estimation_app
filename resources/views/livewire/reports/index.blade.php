<div class="space-y-6" x-data="{ 
    activeTab: @entangle('activeTab'),
    currencySymbol: '{{ $currencySymbol }}'
}" x-init="
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        document.head.appendChild(script);
    }
">
    <!-- Header & Global Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-white rounded-2xl shadow-sm p-8 border border-slate-100 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <x-heroicon-o-presentation-chart-line class="h-32 w-32" />
        </div>
        <div class="relative z-10">
            <h1 class="text-3xl font-black tracking-tight text-slate-900 italic uppercase">System Intel Explorer</h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Global business metrics synchronized with real-time operations.</p>
        </div>
        <div class="mt-6 md:mt-0 flex items-center space-x-4 relative z-10">
            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-slate-200 hover:bg-slate-800 transition-all active:scale-95">
                <x-heroicon-o-arrow-down-tray class="mr-2 h-4 w-4" />
                Export Intelligence
            </button>
            <div class="h-10 w-px bg-slate-200 mx-2"></div>
            <select wire:model.live="filter"
                class="rounded-xl border-slate-200 py-2.5 pl-4 pr-10 text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-indigo-500 appearance-none bg-white">
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="relative">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-1">Origin User</label>
            <select wire:model.live="userId" class="w-full rounded-xl border-slate-100 text-sm py-2 px-3 focus:ring-indigo-500 bg-slate-50/50">
                <option value="">Full Organization</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-1">Target Client</label>
            <select wire:model.live="clientId" class="w-full rounded-xl border-slate-100 text-sm py-2 px-3 focus:ring-indigo-500 bg-slate-50/50">
                <option value="">Full Client List</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="relative">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-1">Life-Cycle Stage</label>
            <select wire:model.live="status" class="w-full rounded-xl border-slate-100 text-sm py-2 px-3 focus:ring-indigo-500 bg-slate-50/50">
                <option value="all">Unified View</option>
                <option value="draft">Draft Protocol</option>
                <option value="sent">Dispatched</option>
                <option value="accepted">Finalized (Won)</option>
                <option value="declined">Rejected (Lost)</option>
            </select>
        </div>
        <div class="flex space-x-2">
            <div class="flex-1">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-1">Temporal Start</label>
                <input type="date" wire:model.live="customStartDate" class="w-full rounded-xl border-slate-100 text-xs py-2 px-3 focus:ring-indigo-500 bg-slate-50/50">
            </div>
            <div class="flex-1">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-1">Temporal End</label>
                <input type="date" wire:model.live="customEndDate" class="w-full rounded-xl border-slate-100 text-xs py-2 px-3 focus:ring-indigo-500 bg-slate-50/50">
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 z-[100] items-center justify-center bg-slate-950/20 backdrop-blur-md">
        <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center border border-slate-200">
            <div class="relative">
                <div class="h-16 w-16 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="h-2 w-2 bg-indigo-600 rounded-full"></div>
                </div>
            </div>
            <span class="mt-6 text-sm font-black text-slate-900 uppercase tracking-widest">Compiling Database</span>
            <span class="mt-1 text-xs text-slate-400">Optimizing system queries...</span>
        </div>
    </div>

    <!-- Performance Bento Hub -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Revenue Card -->
        <div class="group relative overflow-hidden rounded-[2rem] bg-indigo-600 p-8 text-white shadow-xl shadow-indigo-100 transition-all hover:-translate-y-1 hover:shadow-2xl cursor-pointer"
             onclick="window.location.href='{{ route('estimates.index', ['client_status' => 'accepted']) }}'">
            <div class="absolute -right-4 -top-4 opacity-10 group-hover:rotate-12 transition-transform duration-500">
                <x-heroicon-o-circle-stack class="h-32 w-32" />
            </div>
            <div class="relative z-10 h-full flex flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div class="rounded-2xl bg-white/20 p-3 backdrop-blur-md">
                        <x-heroicon-o-banknotes class="h-6 w-6" />
                    </div>
                    <div class="flex items-center space-x-1 font-black text-sm {{ $revenue['growth'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                        <span>{{ $revenue['growth'] >= 0 ? '↑' : '↓' }} {{ abs($revenue['growth']) }}%</span>
                    </div>
                </div>
                <div class="mt-auto">
                    <h3 class="text-xs font-black text-indigo-200 uppercase tracking-[0.2em]">Gross Revenue</h3>
                    <p class="text-3xl font-black mt-2 leading-none tracking-tight">{{ $currencySymbol }}{{ number_format($revenue['total'], 0) }}</p>
                    <div class="mt-6">
                        <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-indigo-300 mb-1.5 line-clamp-1">
                            <span>System Target</span>
                            <span>{{ $goalProgress }}%</span>
                        </div>
                        <div class="h-1.5 w-full bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-white rounded-full transition-all duration-1000" style="width: {{ $goalProgress }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pipeline Value -->
        <div class="group relative overflow-hidden rounded-[2rem] bg-white p-8 shadow-sm border border-slate-100 transition-all hover:border-slate-300 cursor-pointer"
             onclick="window.location.href='{{ route('estimates.index', ['status' => 'open']) }}'">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div class="rounded-2xl bg-indigo-50 p-3 text-indigo-600">
                        <x-heroicon-o-bolt class="h-6 w-6" />
                    </div>
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Active Ops</span>
                </div>
                <div class="mt-auto">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Pipeline Energy</h3>
                    <p class="text-3xl font-black text-slate-900 mt-2">{{ $currencySymbol }}{{ number_format($pipeline['total'], 0) }}</p>
                    <p class="text-xs font-medium text-slate-400 mt-2 italic">{{ $pipeline['count'] }} active protocols in flow</p>
                </div>
            </div>
        </div>

        <!-- System Efficiency (Conversion) -->
        <div class="group relative overflow-hidden rounded-[2rem] bg-white p-8 shadow-sm border border-slate-100 transition-all hover:border-slate-300">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                        <x-heroicon-o-fire class="h-6 w-6" />
                    </div>
                </div>
                <div class="flex flex-col items-center justify-center flex-1 py-4">
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="h-24 w-24 -rotate-90">
                            <circle class="text-slate-100" stroke-width="8" stroke="currentColor" fill="transparent" r="40" cx="48" cy="48"/>
                            <circle class="text-emerald-500" stroke-width="8" stroke-dasharray="{{ ($conversion['rate'] / 100) * 251 }} 251" stroke-linecap="round" stroke="currentColor" fill="transparent" r="40" cx="48" cy="48"/>
                        </svg>
                        <span class="absolute text-xl font-black text-slate-900 tracking-tighter">{{ $conversion['rate'] }}%</span>
                    </div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-4">Conversion Ratio</h3>
                </div>
            </div>
        </div>

        <!-- Approval Velocity -->
        <div class="group relative overflow-hidden rounded-[2rem] bg-slate-900 p-8 text-white shadow-xl shadow-slate-200 transition-all hover:-translate-y-1">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div class="rounded-2xl bg-slate-800 p-3 text-white">
                        <x-heroicon-o-shield-check class="h-6 w-6" />
                    </div>
                </div>
                <div class="mt-auto">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Approval Flow</h3>
                    <p class="text-3xl font-black mt-2">{{ $approval['rate'] }}%</p>
                    <div class="mt-4 flex items-center space-x-2">
                        <div class="flex-1 h-3 bg-slate-800 rounded-lg overflow-hidden flex">
                            <div class="bg-indigo-500" style="width: {{ $approval['rate'] }}%"></div>
                            <div class="bg-rose-500/50" style="width: {{ 100 - $approval['rate'] }}%"></div>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 mt-2 uppercase tracking-wide">{{ $approval['approved'] }} Verified / {{ $approval['pending'] }} Queued</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Command Deck -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-12">
        <!-- Main Chart Deck -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 uppercase italic">Revenue Trajectory</h3>
                        <p class="text-xs text-slate-400 font-medium">Daily accepted value across organization.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            <span class="text-[10px] font-black text-slate-500 uppercase">Growth</span>
                        </div>
                    </div>
                </div>
                <div class="h-[400px] w-full" x-data="{
                    chart: null,
                    init() {
                        this.renderChart(@js($revenueTrend));
                        Livewire.on('chart-updated', (data) => {
                            if (data.revenueTrend) this.renderChart(data.revenueTrend);
                        });
                    },
                    renderChart(data) {
                        if (this.chart) this.chart.destroy();
                        if (!data || data.length === 0) return;
                        
                        const ctx = this.$refs.canvas.getContext('2d');
                        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.map(d => d.date),
                                datasets: [{
                                    label: 'Accepted Revenue',
                                    data: data.map(d => d.total),
                                    borderColor: '#6366f1',
                                    backgroundColor: gradient,
                                    fill: true,
                                    tension: 0.5,
                                    borderWidth: 4,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#6366f1',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { 
                                    legend: { display: false },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: '#0f172a',
                                        titleFont: { weight: 'bold', family: 'Inter' },
                                        bodyFont: { family: 'Inter' },
                                        padding: 12,
                                        displayColors: false,
                                        callbacks: {
                                            label: (ctx) => ' ' + this.currencySymbol + ctx.raw.toLocaleString()
                                        }
                                    }
                                },
                                scales: {
                                    y: { 
                                        beginAtZero: true,
                                        grid: { color: 'rgba(0,0,0,0.02)', drawBorder: false },
                                        ticks: { 
                                            callback: (val) => this.currencySymbol + val.toLocaleString(),
                                            font: { weight: 'bold', size: 10 }
                                        }
                                    },
                                    x: { grid: { display: false }, ticks: { font: { weight: 'bold', size: 10 } } }
                                }
                            }
                        });
                    }
                }" wire:ignore>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
            
            <!-- Dril-down Intelligence Table -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 uppercase">Protocol Deep-Scan</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Individual Record Intelligence</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-50">
                                <th class="px-8 py-4">Reference</th>
                                <th class="px-8 py-4">Node (Client)</th>
                                <th class="px-8 py-4">Status</th>
                                <th class="px-8 py-4 text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($recentEstimates as $estimate)
                                <tr class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-8 py-4 text-xs font-black text-slate-400 group-hover:text-indigo-600 transition-colors">{{ $estimate->estimate_number }}</td>
                                    <td class="px-8 py-4">
                                        <div class="text-sm font-black text-slate-900">{{ $estimate->client->name }}</div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $estimate->creator->name }}</div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest ring-1 ring-inset {{ match($estimate->estimate_status) {
                                            'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                            'sent' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                            'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                            'draft' => 'bg-slate-50 text-slate-600 ring-slate-400/20',
                                            default => 'bg-slate-50 text-slate-400'
                                        } }}">
                                            {{ $estimate->estimate_status }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-4 text-right font-black text-slate-900 font-mono text-sm leading-none italic decoration-indigo-500/20 underline underline-offset-4">
                                        {{ $currencySymbol }}{{ number_format($estimate->grand_total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <x-heroicon-o-magnifying-glass class="h-12 w-12 text-slate-200" />
                                            <span class="mt-4 text-sm font-black text-slate-400 uppercase tracking-widest italic">Signal Obscured: No matching data discovered.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-8 py-4 bg-slate-50 border-t border-slate-100">
                    {{ $recentEstimates->links() }}
                </div>
            </div>
        </div>

        <!-- Sidebar Analytics -->
        <div class="space-y-6">
            <!-- Status Breakdown Donut -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                <h3 class="text-sm font-black text-slate-900 uppercase mb-8">System Health Mix</h3>
                <div class="h-[250px] w-full" x-data="{
                    chart: null,
                    init() {
                        this.renderChart(@js($statusBreakdown));
                        Livewire.on('chart-updated', (data) => {
                            if (data.statusBreakdown) this.renderChart(data.statusBreakdown);
                        });
                    },
                    renderChart(data) {
                        if (this.chart) this.chart.destroy();
                        if (!data) return;
                        
                        const labels = Object.keys(data);
                        const values = Object.values(data);
                        const ctx = this.$refs.canvas.getContext('2d');
                        
                        this.chart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: labels.map(l => l.toUpperCase()),
                                datasets: [{
                                    data: values,
                                    backgroundColor: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#94a3b8'],
                                    borderWidth: 0,
                                    hoverOffset: 12
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '75%',
                                plugins: {
                                    legend: { position: 'bottom', labels: { font: { weight: 'bold', size: 9 }, usePointStyle: true, padding: 20 } }
                                }
                            }
                        });
                    }
                }" wire:ignore>
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Funnel Heatmap -->
            <div class="bg-slate-900 rounded-[2rem] p-8 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <x-heroicon-o-funnel class="h-24 w-24" />
                </div>
                <h3 class="text-sm font-black text-indigo-400 uppercase italic mb-8">Conversion Funnel</h3>
                <div class="space-y-6 relative z-10">
                    @foreach($funnel as $index => $step)
                        @php
                            $percentage = $funnel[0]['count'] > 0 ? ($step['count'] / $funnel[0]['count']) * 100 : 0;
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-end">
                                <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ $step['stage'] }}</span>
                                <span class="text-sm font-black">{{ number_format($step['count']) }}</span>
                            </div>
                            <div class="h-3 w-full bg-slate-800 rounded-lg overflow-hidden flex shadow-inner">
                                <div class="h-full bg-white/60 transition-all duration-1000 delay-{{ $index * 100 }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-10 pt-8 border-t border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-slate-500 uppercase">Growth Potential</span>
                            <span class="text-xl font-black text-emerald-400 mt-1 italic leading-none">+12.4%</span>
                        </div>
                        <x-heroicon-o-arrow-trending-up class="h-8 w-8 text-emerald-400/20" />
                    </div>
                </div>
            </div>

            <!-- Discount Impact -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                <h3 class="text-sm font-black text-slate-900 uppercase mb-8">Pricing Integrity</h3>
                <div class="space-y-6">
                    <div class="p-6 rounded-2xl bg-emerald-50 border border-emerald-100">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-black text-emerald-600 uppercase">Avg. Discount (Won)</span>
                            <x-heroicon-o-check-circle class="h-4 w-4 text-emerald-400" />
                        </div>
                        <p class="text-4xl font-black text-emerald-600 tracking-tight italic line-clamp-1">{{ number_format($discountAnalysis[Estimate::EST_STATUS_ACCEPTED] ?? 0, 1) }}%</p>
                    </div>
                    <div class="p-6 rounded-2xl bg-rose-50 border border-rose-100">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-black text-rose-600 uppercase tracking-tight line-clamp-1">Avg. Discount (Lost)</span>
                            <x-heroicon-o-x-circle class="h-4 w-4 text-rose-400" />
                        </div>
                        <p class="text-4xl font-black text-rose-600 tracking-tight italic line-clamp-1 truncate">{{ number_format($discountAnalysis[Estimate::EST_STATUS_DECLINED] ?? 0, 1) }}%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>