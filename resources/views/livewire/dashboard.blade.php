<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    {{-- Top Section: Welcome & Quick Actions --}}
    <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-930 shadow-2xl ring-1 ring-white/10">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 via-slate-900 to-slate-950"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between px-8 py-10 gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight">
                    Welcome back, {{ explode(' ', auth()->user()->name)[0] }}! <span class="text-indigo-400">⚡️</span>
                </h1>
                <p class="mt-2 text-slate-400 font-medium max-w-xl">
                    Here's a snapshot of your sales performance and pending workflows for today.
                </p>

                {{-- Global Search Bar --}}
                <div class="mt-6 relative max-w-lg">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text" 
                           placeholder="Command: Search estimates, clients or nodes..." 
                           class="block w-full rounded-2xl bg-white/5 border border-white/10 pl-11 pr-4 py-3.5 text-sm font-bold text-white placeholder-slate-500 backdrop-blur-md focus:ring-2 focus:ring-indigo-500 focus:bg-white/10 transition-all shadow-inner"
                           onclick="window.location.href='{{ route('estimates.index') }}'">
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                        <span class="text-[10px] font-black text-slate-500 bg-white/5 px-2 py-1 rounded-md border border-white/10">⌘ K</span>
                    </div>
                </div>
            </div>
            
            {{-- Quick Actions --}}
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('estimates.create') }}" 
                   class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 font-bold text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-105 hover:bg-indigo-500">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Estimate
                </a>
                <a href="{{ route('estimates.index', ['status' => 'pending_approval']) }}" 
                   class="inline-flex items-center gap-2 rounded-2xl bg-white/5 border border-white/10 px-6 py-3 font-bold text-white backdrop-blur-md transition-all hover:bg-white/10">
                    Pending Approvals
                </a>
                <a href="{{ route('estimates.index', ['status' => 'draft']) }}" 
                   class="inline-flex items-center gap-2 rounded-2xl bg-white/5 border border-white/10 px-6 py-3 font-bold text-white backdrop-blur-md transition-all hover:bg-white/10">
                    Drafts
                </a>
            </div>
        </div>
    </div>

    {{-- 1. Next Actions Section (CRITICAL) --}}
    @if(count($nextActions) > 0)
    <section class="space-y-4">
        <h2 class="text-sm font-black uppercase tracking-[0.2em] text-slate-500 flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
            Critical Next Actions
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($nextActions as $action)
            <a href="{{ $action['url'] }}" 
               class="group relative flex items-center justify-between p-6 rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 hover:ring-{{ $action['color'] }}-500 transition-all duration-300">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-{{ $action['color'] }}-50 text-{{ $action['color'] }}-600 group-hover:scale-110 transition-transform">
                        @if($action['id'] === 'approvals')
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @elseif($action['id'] === 'ready_to_send')
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        @elseif($action['id'] === 'expiring')
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 leading-tight">{{ $action['label'] }}</p>
                        <p class="text-xs font-bold text-{{ $action['color'] }}-600/80 uppercase mt-1">Action Required</p>
                    </div>
                </div>
                <svg class="h-5 w-5 text-slate-300 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 2. Pipeline Visualization (Count & Value) --}}
    <section class="rounded-[2.5rem] bg-white p-8 shadow-sm ring-1 ring-slate-100 overflow-hidden relative">
        <div class="absolute top-0 right-0 p-8">
            <svg class="w-32 h-32 text-slate-50 rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V9h-2.83v9.09L9 16.5l-1.41 1.41L12 22.33l4.41-4.42-1.41-1.41-1.59 1.59z"/></svg>
        </div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Active Sales Pipeline</h2>
                <p class="text-sm font-bold text-slate-400">Track movement through the internal workflow</p>
            </div>
            <div class="text-right">
                <span class="text-xs font-black uppercase tracking-widest text-slate-400">Total Pipeline Value</span>
                <p class="text-3xl font-black text-slate-900 tracking-tighter">
                    {{ $currencySymbol }} {{ number_format(collect($pipeline)->sum('value'), 0) }}
                </p>
            </div>
        </div>

        <div class="relative z-10 grid grid-cols-2 lg:grid-cols-7 gap-4">
            @foreach($pipeline as $status => $data)
            <div class="relative group">
                <div class="mb-4 flex flex-col items-center">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ $data['label'] }}</div>
                    <div class="text-lg font-black text-slate-900">{{ $data['count'] }}</div>
                    <div class="text-xs font-bold text-indigo-600/60">{{ $currencySymbol }}{{ number_format($data['value'] / 1000, 1) }}k</div>
                </div>
                {{-- Progress Line --}}
                <div class="h-2 rounded-full overflow-hidden bg-slate-100">
                    <div class="h-full bg-indigo-500 transition-all duration-1000" style="width: {{ $data['count'] > 0 ? '100%' : '0%' }}"></div>
                </div>
                @if(!$loop->last)
                <div class="hidden md:block absolute top-10 -right-2 transform translate-x-1/2 z-20">
                    <svg class="h-4 w-4 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </section>

    {{-- Row 3: Personal Achievement & Metrics --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Personal Achievement Tracker (NEW) --}}
        <div class="lg:col-span-12 rounded-[2.5rem] bg-white p-8 shadow-sm ring-1 ring-slate-100 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden group">
            <div class="absolute -right-20 -bottom-20 h-64 w-64 bg-indigo-50/50 rounded-full blur-3xl group-hover:bg-indigo-100/50 transition-colors"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest">Monthly Growth Target</span>
                    <span class="text-xs font-bold text-slate-400">Target: {{ $currencySymbol }}{{ number_format($metrics['personal_achievement']['target'] / 1000, 1) }}k</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">You've reached <span class="text-indigo-600">{{ round($metrics['personal_achievement']['percent']) }}%</span> of your monthly goal!</h3>
                
                {{-- Progress Bar --}}
                <div class="mt-6">
                    <div class="h-4 w-full bg-slate-100 rounded-full overflow-hidden p-1 shadow-inner">
                        <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000 flex items-center justify-end px-2" style="width: {{ $metrics['personal_achievement']['percent'] }}%">
                            @if($metrics['personal_achievement']['percent'] > 20)
                            <div class="h-1 w-1 rounded-full bg-white animate-pulse"></div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 flex justify-between text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <span>Started</span>
                        <span class="text-indigo-600">Goal: {{ $currencySymbol }}{{ number_format($metrics['personal_achievement']['target'], 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex-shrink-0 text-right">
                <span class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-1">Generated Revenue</span>
                <p class="text-5xl font-black text-slate-900 tracking-tighter italic">
                    {{ $currencySymbol }}{{ number_format($metrics['personal_achievement']['current'], 0) }}
                </p>
                @if($metrics['personal_achievement']['percent'] >= 100)
                    <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-2xl font-black text-xs uppercase tracking-widest animate-bounce">
                        Target Achieved! 🏆
                    </div>
                @endif
            </div>
        </div>

        {{-- Performance Metrics Card --}}
        <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-3xl bg-slate-950 p-6 shadow-xl ring-1 ring-white/10 relative overflow-hidden group">
                <div class="absolute -top-10 -right-10 h-24 w-24 bg-indigo-500/20 blur-2xl rounded-full"></div>
                <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-4">Total Revenue</dt>
                <dd class="text-2xl font-black text-white tracking-tighter">{{ $currencySymbol }}{{ number_format($metrics['total_value'] / 1000, 1) }}k</dd>
                <div class="mt-2 text-[10px] font-bold text-emerald-400 flex items-center gap-1">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2 1 1 0 010 2zm1 2a1 1 0 10-2 0v6a1 1 0 102 0V9z" clip-rule="evenodd" /></svg>
                    THIS MONTH
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 group">
                <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Win Rate</dt>
                <dd class="text-2xl font-black text-slate-900 tracking-tighter">{{ round($metrics['win_rate'], 1) }}%</dd>
                <div class="mt-2 text-[10px] font-bold text-slate-400 flex items-center gap-1 uppercase">Avg. Accepted</div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 group">
                <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Avg Approval</dt>
                <dd class="text-2xl font-black text-slate-900 tracking-tighter">{{ $metrics['avg_approval_time'] }}</dd>
                <div class="mt-2 text-[10px] font-bold text-slate-400 flex items-center gap-1 uppercase">Cycles</div>
            </div>

            <div class="rounded-3xl bg-indigo-50 p-6 shadow-sm ring-1 ring-indigo-200 group relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10">
                    <x-heroicon-o-presentation-chart-line class="h-16 w-16" />
                </div>
                <dt class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-4">Revenue Forecast</dt>
                <dd class="text-2xl font-black text-indigo-900 tracking-tighter">{{ $currencySymbol }}{{ number_format($metrics['forecast_value'] / 1000, 1) }}k</dd>
                <div class="mt-2 text-[10px] font-bold text-indigo-600 flex items-center gap-1 uppercase">Proj. Wins</div>
            </div>
        </div>

        {{-- Smart Alerts & Intelligence Column --}}
        <div class="lg:col-span-4 flex flex-col gap-6" x-data="{ sideTab: 'alerts' }">
            
            {{-- Intelligence Switcher --}}
            <div class="flex p-1.5 bg-slate-100 rounded-2xl gap-1">
                <button @click="sideTab = 'alerts'" :class="sideTab === 'alerts' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'" class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all">Alerts & Targets</button>
                <button @click="sideTab = 'intel'" :class="sideTab === 'intel' ? 'bg-white shadow-sm text-rose-600' : 'text-slate-500'" class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all">Process Intel</button>
            </div>

            <div x-show="sideTab === 'alerts'" class="space-y-6" x-transition>
                {{-- Team Leaderboard (Admin Only) --}}
                @if(count($metrics['leaderboard']) > 0)
                <div class="rounded-[2.5rem] bg-indigo-900 p-6 shadow-xl ring-1 ring-white/10 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <x-heroicon-o-trophy class="h-12 w-12 text-white" />
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-indigo-300 mb-4 flex items-center gap-2">
                        Top Performers
                    </h3>
                    <div class="space-y-3">
                        @foreach($metrics['leaderboard'] as $index => $performer)
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/10">
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-black {{ $index === 0 ? 'text-amber-400' : 'text-slate-400' }}">#{{ $index + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-white truncate">{{ $performer->name }}</p>
                                    <p class="text-[9px] font-bold text-indigo-300 uppercase">{{ $performer->won_count }} Wins</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-white">{{ $currencySymbol }}{{ number_format($performer->total_revenue / 1000, 0) }}k</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Smart Alerts Card --}}
                <div class="rounded-[2.5rem] bg-slate-50 p-6 shadow-inner ring-1 ring-slate-200/50">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-4 flex items-center gap-2">
                        <x-heroicon-o-bell-alert class="h-4 w-4" />
                        Smart Alerts
                    </h3>
                    <div class="space-y-3">
                        @forelse($alerts as $alert)
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                            <div class="h-8 w-8 rounded-xl flex items-center justify-center 
                                @if($alert['type'] === 'danger') bg-rose-50 text-rose-600 @elseif($alert['type'] === 'warning') bg-amber-50 text-amber-600 @else bg-blue-50 text-blue-600 @endif">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ $alert['label'] }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $alert['count'] }} Estimates</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-6 text-slate-400 italic text-xs font-bold">All clear! No alerts today.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-show="sideTab === 'intel'" class="space-y-6" style="display: none;" x-transition>
                {{-- Bottleneck Radar (Admin Only) --}}
                @if(count($metrics['bottlenecks']) > 0)
                <div class="rounded-[2.5rem] bg-rose-50 border border-rose-100 p-6">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-rose-600 mb-4 flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        Approval Bottlenecks
                    </h3>
                    <div class="space-y-4">
                        @foreach($metrics['bottlenecks'] as $bottleneck)
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700">{{ $bottleneck->name }}</span>
                            <span class="px-2 py-0.5 rounded-lg bg-rose-600 text-white text-[10px] font-black uppercase tracking-widest">{{ $bottleneck->pending_count }} STUCK</span>
                        </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-[10px] text-rose-400 font-bold uppercase tracking-widest italic">* Delayed > 24 hours</p>
                </div>
                @endif

                {{-- Client Engagement Radar --}}
                @if(count($metrics['client_engagement']) > 0)
                <div class="rounded-[2.5rem] bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-4 flex items-center gap-2">
                        Client Engagement Radar
                    </h3>
                    <div class="space-y-4">
                        @foreach($metrics['client_engagement'] as $engagement)
                        <div class="relative">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-slate-900 truncate max-w-[150px]">{{ $engagement->client->name ?? 'Unknown' }}</span>
                                <span class="text-[10px] font-black text-indigo-600 uppercase">{{ $engagement->total_views }} Views</span>
                            </div>
                            <div class="h-1 w-full bg-slate-50 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: {{ min(100, $engagement->total_views * 10) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-4 flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                Smart Alerts
            </h3>
            <div class="space-y-3">
                @forelse($alerts as $alert)
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                    <div class="h-8 w-8 rounded-xl flex items-center justify-center 
                        @if($alert['type'] === 'danger') bg-rose-50 text-rose-600 @elseif($alert['type'] === 'warning') bg-amber-50 text-amber-600 @else bg-blue-50 text-blue-600 @endif">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-900 truncate">{{ $alert['label'] }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $alert['count'] }} Estimates</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-6 text-slate-400 italic text-xs font-bold">All clear! No alerts today.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Row 4: Lists & Feed --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Recent Estimates & Expiring Tabbed View --}}
        <div class="lg:col-span-2 rounded-[2.5rem] bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden" x-data="{ activeTab: 'recent' }">
            <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                <div class="flex gap-6">
                    <button @click="activeTab = 'recent'" :class="activeTab === 'recent' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400'" class="font-black text-sm tracking-tight pb-1 transition-all">Recent Activity</button>
                    <button @click="activeTab = 'expiring'" :class="activeTab === 'expiring' ? 'text-rose-600 border-b-2 border-rose-600' : 'text-slate-400'" class="font-black text-sm tracking-tight pb-1 transition-all flex items-center gap-2">
                        Next Expiring
                        @if(count($expiring_estimates) > 0)
                            <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                        @endif
                    </button>
                </div>
                <a href="{{ route('estimates.index') }}" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-500 bg-indigo-50 px-3 py-1.5 rounded-full">Explore All</a>
            </div>
            
            <div x-show="activeTab === 'recent'" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Estimate</th>
                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Total</th>
                                <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Activity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recent_estimates as $estimate)
                            <tr class="group hover:bg-slate-50/80 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-500 font-black text-xs group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                            #{{ substr($estimate->estimate_number, -4) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('estimates.show', $estimate) }}" class="text-sm font-black text-slate-900 hover:text-indigo-600 block">
                                                {{ $estimate->title }}
                                            </a>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $estimate->client->name ?? 'Unknown Client' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-5">
                                    @php
                                        $statusStyles = match($estimate->estimate_status) {
                                            'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                            'sent' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                            'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                            'pending_approval' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-widest ring-1 ring-inset {{ $statusStyles }}">
                                        {{ str_replace('_', ' ', $estimate->estimate_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-5">
                                    <span class="text-sm font-black text-slate-900">{{ $currencySymbol }}{{ number_format($estimate->grand_total, 2) }}</span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase whitespace-nowrap">{{ $estimate->updated_at->diffForHumans() }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="activeTab === 'expiring'" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4" style="display: none;">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-rose-50/50">
                            <tr>
                                <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-rose-600">At Risk Protocol</th>
                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-rose-600 text-center">Expires In</th>
                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-rose-600">Value</th>
                                <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-rose-600 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($expiring_estimates as $estimate)
                            <tr class="group hover:bg-rose-50/30 transition-colors">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-xs">
                                            ⌛️
                                        </div>
                                        <div>
                                            <a href="{{ route('estimates.show', $estimate) }}" class="text-sm font-black text-slate-900 hover:text-rose-600 block">
                                                {{ $estimate->title }}
                                            </a>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $estimate->client->name ?? 'Unknown Client' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    <span class="text-[10px] font-black uppercase tracking-widest {{ $estimate->expires_at->diffInHours() < 24 ? 'text-rose-600 animate-pulse' : 'text-slate-600' }}">
                                        {{ $estimate->expires_at->diffForHumans(null, true) }}
                                    </span>
                                </td>
                                <td class="px-4 py-5">
                                    <span class="text-sm font-black text-slate-900">{{ $currencySymbol }}{{ number_format($estimate->grand_total, 2) }}</span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <a href="{{ route('estimates.show', $estimate) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-rose-600 text-white shadow-lg shadow-rose-200 hover:scale-110 transition-transform">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="h-16 w-16 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-4">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Pipeline Healthy: No At-Risk Protocols</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Hot Leads & Activity column --}}
        <div class="space-y-8">
             {{-- Hot Leads --}}
             <div class="rounded-[2.5rem] bg-slate-900 p-8 shadow-xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 to-slate-950"></div>
                <div class="relative z-10 flex items-center justify-between mb-8">
                    <h3 class="font-black text-white tracking-tight">Engagement Hotlist</h3>
                    <div class="h-8 w-8 rounded-full bg-rose-500 flex items-center justify-center animate-pulse">
                        <svg class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M13.5 4.938a7 7 0 11-9.006 1.737c.202-.257.59-.218.793.08a5.002 5.002 0 002.502 8.01 5 5 0 005.15-1.928 2.5 2.5 0 013.9 1.156c.196.883-.559 1.543-1.42 1.492a2.503 2.503 0 01-1.72-.756.75.75 0 00-1.06 1.06 4.003 4.003 0 005.418-5.32c-.066-.192-.28-.307-.478-.266a2.5 2.5 0 01-2.91-4.225.75.75 0 00-.916-1.04z" clip-rule="evenodd" /></svg>
                    </div>
                </div>
                
                <div class="relative z-10 space-y-4">
                    @forelse($hot_leads as $lead)
                    <a href="{{ route('estimates.show', $lead) }}" class="block p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-black text-white">{{ $lead->client->name ?? 'Unknown Client' }}</p>
                                <div class="flex gap-0.5 mt-2">
                                    @for($i = 0; $i < 5; $i++)
                                        <div class="h-1 w-3 rounded-full {{ $i < ceil($lead->engagement_score / 20) ? 'bg-rose-500' : 'bg-slate-700' }}"></div>
                                    @endfor
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-rose-400">{{ $currencySymbol }}{{ number_format($lead->grand_total, 0) }}</p>
                                <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">Score: {{ $lead->engagement_score }}</p>
                            </div>
                        </div>
                    </a>
                    @empty
                    <p class="text-center text-xs text-slate-500 font-bold py-4">No high engagement detected yet.</p>
                    @endforelse
                </div>
             </div>

             {{-- Activity Feed --}}
             <div class="rounded-[2.5rem] bg-white p-8 shadow-sm ring-1 ring-slate-100">
                <h3 class="font-black text-slate-900 tracking-tight mb-8">Recent Events</h3>
                <div class="space-y-6">
                    @forelse($recent_activities as $activity)
                    <div class="flex gap-4 relative">
                        @if(!$loop->last)
                        <div class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-slate-100"></div>
                        @endif
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-slate-100 ring-4 ring-white flex items-center justify-center">
                                <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1 pt-1">
                            <p class="text-xs font-bold text-slate-600 leading-relaxed">{{ $activity->description }}</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase mt-1">{{ $activity->created_at->diffForHumans(null, true) }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-xs text-slate-400 font-bold py-4">No recent activity.</p>
                    @endforelse
                </div>
             </div>
        </div>
    </div>
</div>
