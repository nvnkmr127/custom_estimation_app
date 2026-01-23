<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
    
    {{-- Hero Section: Mesh Gradient & Welcome --}}
    <div class="relative overflow-hidden rounded-[2.5rem] bg-indigo-600 shadow-2xl ring-1 ring-white/20">
        {{-- Abstract Decorative Elements --}}
        <div class="absolute -top-24 -left-20 h-96 w-96 rounded-full bg-purple-500 blur-3xl opacity-50 mix-blend-multiply animate-pulse"></div>
        <div class="absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-teal-400 blur-3xl opacity-50 mix-blend-multiply animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/90 via-violet-600/90 to-fuchsia-600/90 backdrop-blur-sm"></div>
        
        {{-- Mesh Noise Overlay --}}
        <div class="absolute inset-0 opacity-20" style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E&quot;);"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between px-8 py-12 gap-6">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80 ring-1 ring-white/20 mb-4 backdrop-blur-md">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-400 animate-pulse"></span>
                    Live Dashboard
                </span>
                <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight drop-shadow-sm">
                    Hello, {{ explode(' ', auth()->user()->name)[0] }}! <span class="text-white/60 font-medium">👋</span>
                </h1>
                <p class="mt-4 text-lg text-indigo-100 max-w-xl font-medium leading-relaxed">
                    Here's what's happening with your estimates today. You have <span class="text-white font-bold decoration-teal-400 decoration-2 underline underline-offset-2">{{ $stats['tasks_pending'] }} pending tasks</span> requiring attention.
                </p>
            </div>
            <div class="flex-shrink-0">
                 <a href="{{ route('estimates.create') }}"
                    class="group relative inline-flex items-center gap-3 overflow-hidden rounded-2xl bg-white px-8 py-4 font-bold text-indigo-600 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-indigo-500/30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">
                    <span class="absolute inset-0 bg-gradient-to-r from-teal-50 to-indigo-50 opacity-0 transition-opacity group-hover:opacity-100"></span>
                    <span class="relative flex items-center gap-2">
                        <svg class="h-6 w-6 transition-transform duration-300 group-hover:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Estimate
                    </span>
                </a>
            </div>
        </div>
    </div>

    {{-- Bento Grid Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-6">
        
        {{-- Pipeline Card (Large) --}}
        <div class="lg:col-span-5 relative group overflow-hidden rounded-[2rem] bg-white p-8 shadow-content hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 ring-1 ring-slate-100">
             <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity text-indigo-600">
                <svg class="w-32 h-32 transform translate-x-8 -translate-y-8 rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V9h-2.83v9.09L9 16.5l-1.41 1.41L12 22.33l4.41-4.42-1.41-1.41-1.59 1.59z"/></svg>
            </div>
            <dt class="text-sm font-bold uppercase tracking-widest text-slate-400">Total Pipeline</dt>
            <dd class="mt-4 flex items-baseline gap-2">
                <span class="text-4xl md:text-5xl font-black text-slate-900 tracking-tighter">
                   {{ number_format($pipeline_revenue, 0) }}<span class="text-2xl text-slate-400">.00</span>
                </span>
            </dd>
            <div class="mt-8 flex items-center gap-4">
                 <div class="flex-1 overflow-hidden rounded-full bg-slate-100 h-2">
                    <div class="h-full bg-indigo-500 rounded-full" style="width: 70%"></div>
                 </div>
                 <span class="text-xs font-bold text-indigo-600">70% Goal</span>
            </div>
             <div class="mt-4 flex gap-4">
                <div class="flex items-center gap-2 rounded-xl bg-indigo-50 px-3 py-2">
                    <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                     <span class="text-xs font-bold text-indigo-700">{{ $stats['sent'] }} Sent</span>
                </div>
                 <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                    <div class="h-2 w-2 rounded-full bg-slate-400"></div>
                     <span class="text-xs font-bold text-slate-600">{{ $stats['draft'] }} Draft</span>
                </div>
            </div>
        </div>

        {{-- Revenue Card (Medium) --}}
        <div class="lg:col-span-4 relative group overflow-hidden rounded-[2rem] bg-slate-900 p-8 shadow-xl transition-all duration-300 ring-1 ring-white/10 hover:ring-teal-500/50">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900"></div>
             {{-- Decorative Glow --}}
            <div class="absolute -bottom-24 -left-24 h-48 w-48 rounded-full bg-teal-500/20 blur-3xl"></div>

            <div class="relative z-10">
                <dt class="flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-400 shadow-[0_0_8px_rgba(45,212,191,0.8)]"></span>
                    Converted
                </dt>
                <dd class="mt-4">
                    <span class="text-4xl md:text-5xl font-black text-white tracking-tighter">
                    {{ number_format($converted_revenue, 0) }}
                    </span>
                </dd>
                <div class="mt-6 flex items-center gap-3">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-teal-500/10 px-2.5 py-1 text-sm font-bold text-teal-400 ring-1 ring-inset ring-teal-500/20">
                         <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2 1 1 0 010 2zm1 2a1 1 0 10-2 0v6a1 1 0 102 0V9z" clip-rule="evenodd" /></svg>
                        {{ round($conversion_rate, 1) }}% Win Rate
                    </span>
                     <span class="text-xs font-medium text-slate-400">vs last month</span>
                </div>
            </div>
        </div>

        {{-- Forecast Card (Small) --}}
        <div class="lg:col-span-3 relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-violet-500 to-purple-600 p-8 text-white shadow-lg shadow-purple-500/20 hover:scale-[1.02] transition-transform duration-300">
             <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
            <dt class="text-xs font-bold uppercase tracking-widest text-purple-100/80">Forecast (70%)</dt>
            <dd class="mt-2 text-3xl font-black tracking-tight">
                {{ number_format($weightedForecast, 0) }}
            </dd>
            <div class="mt-8 pt-6 border-t border-white/20">
                 <p class="text-xs font-medium text-purple-100">Weighted probability based on active pipeline.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        {{-- Left Column (Stats & Lists) --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- Restored Stats Cards (Grid of 3) --}}
             <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="group relative overflow-hidden rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 transition-all hover:shadow-md hover:ring-indigo-100">
                    <dt class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <span class="p-1 rounded bg-slate-100 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        Estimate Volume
                    </dt>
                    <dd class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900">{{ $stats['total'] }}</span>
                         <span class="text-[10px] font-bold text-slate-400 uppercase">All Time</span>
                    </dd>
                </div>
                 <div class="group relative overflow-hidden rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 transition-all hover:shadow-md hover:ring-indigo-100">
                    <dt class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <span class="p-1 rounded bg-slate-100 group-hover:bg-amber-50 group-hover:text-amber-600 transition-colors">
                           <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        Pending Tasks
                    </dt>
                    <dd class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-amber-500">{{ $stats['tasks_pending'] }}</span>
                        <span class="text-[10px] font-bold text-amber-200 uppercase">Action Needed</span>
                    </dd>
                </div>
                 <div class="group relative overflow-hidden rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 transition-all hover:shadow-md hover:ring-indigo-100">
                    <dt class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                         <span class="p-1 rounded bg-slate-100 group-hover:bg-rose-50 group-hover:text-rose-600 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </span>
                        Overdue
                    </dt>
                    <dd class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-rose-500">{{ $stats['tasks_overdue'] }}</span>
                         <span class="text-[10px] font-bold text-rose-200 uppercase">Urgent</span>
                    </dd>
                </div>
             </div>

            {{-- Recent Estimates Table --}}
            <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-900">Recent Estimates</h3>
                     <a href="{{ route('estimates.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">View All</a>
                </div>
                <ul role="list" class="divide-y divide-slate-50">
                    @forelse($recent_estimates as $estimate)
                        <li class="group relative flex items-center justify-between gap-x-6 px-6 py-5 hover:bg-slate-50 transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-x-3">
                                    <p class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                        <a href="{{ route('estimates.show', $estimate) }}">
                                            <span class="absolute inset-0"></span>
                                            {{ $estimate->title }}
                                        </a>
                                    </p>
                                    @php
                                        $statusStyles = match($estimate->status) {
                                            'accepted' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
                                            'sent' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                            'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                            default => 'bg-slate-50 text-slate-600 ring-slate-500/20',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset {{ $statusStyles }}">
                                        {{ ucfirst($estimate->status) }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-x-2 text-xs font-medium text-slate-500">
                                    <span>#{{ $estimate->estimate_number }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $estimate->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-4">
                                <span class="text-sm font-bold text-slate-900">{{ number_format($estimate->grand_total, 2) }}</span>
                                <svg class="h-5 w-5 flex-none text-slate-300 group-hover:text-indigo-400 group-hover:translate-x-1 transition-all" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </li>
                    @empty
                         <li class="px-6 py-8 text-center text-sm text-slate-500 italic">No recent estimates found.</li>
                    @endforelse
                </ul>
            </div>
            
            {{-- Restored Recent Tasks List --}}
            <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-900">Your Tasks</h3>
                    <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">View All</a>
                </div>
                <ul role="list" class="divide-y divide-slate-50">
                    @forelse($recent_tasks as $task)
                        <li class="group flex items-center justify-between gap-x-6 px-6 py-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                 <div class="flex-none">
                                     <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $task->is_completed ? 'bg-teal-50 text-teal-600' : 'bg-amber-50 text-amber-600' }}">
                                         @if($task->is_completed)
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                         @else
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                         @endif
                                     </span>
                                 </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $task->title }}</p>
                                    <p class="text-xs font-medium text-slate-500">
                                        Due {{ $task->due_date ? $task->due_date->format('M d') : 'No date' }} &bull;
                                         <span class="{{ $task->is_completed ? 'text-teal-600' : 'text-amber-600' }}">
                                             {{ $task->is_completed ? 'Completed' : 'Pending' }}
                                         </span>
                                    </p>
                                </div>
                            </div>
                        </li>
                    @empty
                         <li class="px-6 py-8 text-center text-sm text-slate-500 italic">No Pendiing Tasks.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Right Column (Hot Leads & Activity) --}}
        <div class="space-y-8">
             {{-- Hot Leads Widget --}}
             @if(isset($hot_leads) && $hot_leads->count() > 0)
                <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white shadow-xl ring-1 ring-white/10">
                     <div class="absolute top-0 right-0 -mt-10 -mr-10 h-64 w-64 rounded-full bg-rose-500/20 blur-3xl animate-pulse"></div>
                     <div class="relative z-10 px-6 py-6 border-b border-white/10 flex items-center gap-3">
                         <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-orange-500 shadow-lg shadow-rose-500/30">
                            <svg class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M13.5 4.938a7 7 0 11-9.006 1.737c.202-.257.59-.218.793.08a5.002 5.002 0 002.502 8.01 5 5 0 005.15-1.928 2.5 2.5 0 013.9 1.156c.196.883-.559 1.543-1.42 1.492a2.503 2.503 0 01-1.72-.756.75.75 0 00-1.06 1.06 4.003 4.003 0 005.418-5.32c-.066-.192-.28-.307-.478-.266a2.5 2.5 0 01-2.91-4.225.75.75 0 00-.916-1.04z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Hot Leads</h3>
                            <p class="text-xs font-medium text-rose-200">High engagement detected</p>
                        </div>
                    </div>
                     <ul role="list" class="relative z-10 divide-y divide-white/10">
                        @foreach($hot_leads as $lead)
                            <li class="relative hover:bg-white/5 transition-colors px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <div class="flex items-start gap-2">
                                            <p class="text-sm font-bold text-white truncate">
                                                <a href="{{ route('estimates.show', $lead) }}">
                                                    <span class="absolute inset-0"></span>
                                                    {{ $lead->client->name ?? 'Unknown Client' }}
                                                </a>
                                            </p>
                                        </div>
                                         <div class="mt-1 flex items-center gap-2">
                                             {{-- Fire Intensity Bar --}}
                                            <div class="flex gap-0.5">
                                                @for($i = 0; $i < 5; $i++)
                                                    <div class="h-1.5 w-3 rounded-full {{ $i < ceil($lead->engagement_score / 20) ? 'bg-rose-500' : 'bg-slate-700' }}"></div>
                                                @endfor
                                            </div>
                                            <span class="text-[10px] font-medium text-slate-400">Score: {{ $lead->engagement_score }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-rose-300">{{ number_format($lead->grand_total, 0) }}</p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                     <div class="relative z-10 px-6 py-4 bg-rose-500/10 border-t border-white/10">
                        <a href="#" class="text-xs font-bold text-rose-300 hover:text-white transition-colors flex items-center gap-1">
                            View All Opportunities &rarr;
                        </a>
                     </div>
                </div>
            @endif

            {{-- Activity Timeline --}}
            <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 p-6">
                <h3 class="font-bold text-slate-900 mb-6">Activity Feed</h3>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse($recent_activities as $activity)
                            <li>
                                <div class="relative pb-8">
                                    @if (!$loop->last)
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-100" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center ring-4 ring-white">
                                               <div class="h-2 w-2 rounded-full bg-indigo-600"></div>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-xs text-slate-600">
                                                    {{ $activity->description }}
                                                </p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-[10px] text-slate-400 font-medium">
                                                {{ $activity->created_at->diffForHumans(null, true) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                             <li class="text-center text-sm text-slate-500 italic">No recent activity.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>