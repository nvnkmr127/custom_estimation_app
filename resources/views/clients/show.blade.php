<x-app-layout>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&display=swap" rel="stylesheet">
    <style>
        .premium-font { font-family: 'Plus Jakarta Sans', sans-serif; }
        .heading-font { font-family: 'Outfit', sans-serif; }
        .accent-font { font-family: 'Fraunces', serif; }
        .mono-font { font-family: 'JetBrains Mono', monospace; }
    </style>
    <div class="premium-font text-slate-700">
    <!-- Header Strategy: Identity & Navigation -->
    <div class="mb-10 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                <!-- Advanced Entity Avatar -->
                <div class="relative group">
                    <div class="h-20 w-20 rounded-[2rem] bg-gradient-to-tr from-indigo-600 to-violet-700 flex items-center justify-center text-white text-3xl font-black shadow-2xl shadow-indigo-200 group-hover:rotate-6 transition-transform duration-500">
                        {{ substr($client->name, 0, 1) }}
                    </div>
                    <div class="absolute -bottom-2 -right-2 h-8 w-8 bg-white rounded-2xl flex items-center justify-center shadow-lg ring-4 ring-slate-50">
                        <div class="h-4 w-4 rounded-full bg-emerald-500 animate-pulse"></div>
                    </div>
                </div>

                <div>
                    <nav class="flex mb-3" aria-label="Breadcrumb">
                        <ol role="list" class="flex items-center space-x-2.5">
                            <li>
                                <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </a>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 flex-shrink-0 text-slate-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                                <a href="{{ route('clients.index') }}" class="ml-2 text-sm font-bold text-slate-400 hover:text-indigo-600 transition-colors tracking-tight">Clients</a>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 flex-shrink-0 text-slate-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-2 text-sm font-bold text-slate-900 tracking-tight">Client Details</span>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="text-4xl accent-font font-black tracking-tight text-slate-900 sm:text-5xl">{{ $client->name }}</h1>
                    <div class="flex items-center gap-3 mt-3">
                        @if($client->company)
                            <span class="inline-flex items-center bg-slate-900 text-white text-[10px] heading-font font-black uppercase tracking-widest px-3 py-1.5 rounded-xl shadow-sm">
                                {{ $client->company }}
                            </span>
                        @endif
                        <span class="text-xs font-bold text-slate-400 heading-font">Client ID: <span class="mono-font text-indigo-500">#{{ $client->id }}</span></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                @can('update', $client)
                <a href="{{ route('clients.edit', $client) }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 hover:shadow-md transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Client
                </a>
                @endcan
                <a href="{{ route('estimates.create', ['client_id' => $client->id]) }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-6 py-3 text-sm font-bold text-white shadow-xl shadow-slate-200 hover:bg-indigo-600 hover:-translate-y-1 transition-all duration-300">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Estimate
                </a>
            </div>
        </div>
    </div>

    <!-- Analytics Deck (Stats Grid) -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-10 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-100">
        <!-- Revenue Intelligence -->
        <div class="group bg-slate-900 rounded-[2.5rem] p-7 shadow-2xl shadow-slate-200 transition-all duration-500 hover:shadow-indigo-100">
            <div class="flex items-center justify-between mb-8">
                <div class="h-12 w-12 rounded-2xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[10px] heading-font font-black uppercase tracking-[0.2em] text-slate-500">Total Value</span>
            </div>
            <div class="text-4xl font-black text-white tracking-tight heading-font">{{ \App\Models\Setting::getCurrencySymbol() }}{{ number_format($stats['total_value'], 2) }}</div>
            <div class="mt-2 text-xs font-bold text-indigo-400 bg-indigo-500/10 inline-block px-2.5 py-1 rounded-lg heading-font">Confirmed Revenue</div>
        </div>

        <!-- Volume Tracker -->
        <div class="bg-white rounded-[2.5rem] p-7 shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:shadow-xl hover:ring-indigo-100">
            <div class="flex items-center justify-between mb-8">
                <div class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="text-[10px] heading-font font-black uppercase tracking-[0.2em] text-slate-400">Total Estimates</span>
            </div>
            <div class="text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['total_estimates'] }}</div>
            <div class="mt-2 text-xs font-bold text-slate-500 italic">Total Created</div>
        </div>

        <!-- conversion Intelligence -->
        <div class="bg-white rounded-[2.5rem] p-7 shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:shadow-xl hover:ring-emerald-100">
            <div class="flex items-center justify-between mb-8">
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[10px] heading-font font-black uppercase tracking-[0.2em] text-slate-400">Accepted</span>
            </div>
            <div class="text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['accepted_estimates'] }}</div>
            <div class="mt-2 text-xs font-bold text-emerald-600 bg-emerald-50 inline-block px-2 py-1 rounded-lg heading-font">Accepted Estimates</div>
        </div>

        <!-- Activity Sentinel -->
        <div class="bg-white rounded-[2.5rem] p-7 shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:shadow-xl hover:ring-indigo-100">
            <div class="flex items-center justify-between mb-8">
                <div class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[10px] heading-font font-black uppercase tracking-[0.2em] text-slate-400">Last Active</span>
            </div>
            <div class="text-xl font-black text-slate-900 leading-tight mt-auto">
                {{ $stats['last_engagement']->diffForHumans() }}
            </div>
            <div class="mt-2 text-xs font-bold text-slate-500">Last Engagement</div>
        </div>
    </div>

    <!-- Operations View: Main Content -->
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-200">
        <!-- Sidebar Entity Info (4 cols) -->
        <div class="lg:col-span-4 space-y-8">
            <!-- Contact Card: Glassmorphism Style -->
            <div class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-sm ring-1 ring-slate-200 overflow-hidden group">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-xs font-black heading-font uppercase tracking-[0.2em] text-slate-400">Contact Information</h2>
                </div>
                <div class="p-8">
                    <div class="space-y-8">
                        @if($client->email)
                        <div class="group/item">
                            <label class="block text-[10px] heading-font font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Email Address</label>
                            <a href="mailto:{{ $client->email }}" class="flex items-center p-4 rounded-2xl bg-slate-50 ring-1 ring-slate-100 hover:bg-white hover:ring-indigo-200 hover:shadow-lg hover:shadow-indigo-50 transition-all duration-300">
                                <div class="h-10 w-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mr-4 group-hover/item:scale-110 transition-transform">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-slate-900 group-hover/item:text-indigo-600 transition-colors truncate heading-font">{{ $client->email }}</span>
                            </a>
                        </div>
                        @endif

                        @if($client->phone)
                        <div class="group/item">
                            <label class="block text-[10px] heading-font font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Phone Number</label>
                            <a href="tel:{{ $client->phone }}" class="flex items-center p-4 rounded-2xl bg-slate-50 ring-1 ring-slate-100 hover:bg-white hover:ring-indigo-200 hover:shadow-lg hover:shadow-indigo-50 transition-all duration-300">
                                <div class="h-10 w-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center mr-4 group-hover/item:scale-110 transition-transform">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-slate-900 group-hover/item:text-violet-600 transition-colors">{{ $client->phone }}</span>
                            </a>
                        </div>
                        @endif

                        @if($client->address || $client->city)
                        <div class="group/item">
                            <label class="block text-[10px] heading-font font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Office Location</label>
                            <div class="flex items-start p-4 rounded-2xl bg-slate-50 ring-1 ring-slate-100">
                                <div class="h-10 w-10 rounded-xl bg-slate-200 text-slate-500 flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="text-xs font-bold text-slate-600 leading-relaxed uppercase tracking-tighter">
                                    @if($client->address) {{ $client->address }}<br> @endif
                                    @if($client->city || $client->state || $client->zip)
                                        <span class="text-indigo-600 font-black">{{ $client->city }}</span> {{ $client->state }} {{ $client->zip }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Intelligence Note: Deep Minimal -->
            @if($client->notes)
            <div class="relative group bg-slate-900 rounded-[2.5rem] p-8 shadow-2xl shadow-indigo-100 overflow-hidden">
                <div class="absolute -top-10 -right-10 h-32 w-32 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all duration-700"></div>
                <div class="relative">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                        <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Client Notes</h2>
                    </div>
                    <div class="text-[14px] leading-relaxed font-bold text-white/80 italic selection:bg-indigo-500">
                        {{ $client->notes }}
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Activity Feed: (8 cols) -->
        <div class="lg:col-span-8 space-y-8">
            <div class="bg-white/60 backdrop-blur-xl rounded-[2.5rem] shadow-sm ring-1 ring-slate-200 overflow-hidden">
                <div class="px-8 py-7 border-b border-slate-100 flex items-center justify-between bg-slate-50/20">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 tracking-tight heading-font">Estimate History</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5 heading-font">Recent 20 activities</p>
                        </div>
                    </div>
                    <a href="{{ route('estimates.index', ['client_id' => $client->id]) }}" class="group inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all heading-font">
                        View All
                        <svg class="ml-2 h-3.5 w-3.5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
                <div class="overflow-x-auto overflow-y-auto max-h-[600px] custom-scrollbar">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-white/40 backdrop-blur-md sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 heading-font">Estimate #</th>
                                <th scope="col" class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 heading-font">Date</th>
                                <th scope="col" class="px-8 py-5 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 heading-font">Status</th>
                                <th scope="col" class="px-8 py-5 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 heading-font">Total Amount</th>
                                <th scope="col" class="relative px-8 py-5">
                                    <span class="sr-only">Operations</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse($client->estimates as $estimate)
                                <tr class="group hover:bg-slate-50/50 transition-all duration-200 cursor-pointer" onclick="window.location='{{ route('estimates.show', $estimate) }}'">
                                    <td class="whitespace-nowrap px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-900 group-hover:text-indigo-600 transition-colors uppercase tracking-tight">{{ $estimate->estimate_number }}</span>
                                            @if($estimate->title)
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 truncate max-w-[140px]">{{ $estimate->title }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-8 py-6">
                                        <span class="text-xs font-bold text-slate-500 uppercase">{{ $estimate->estimate_date->format('d M, Y') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-8 py-6">
                                        <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-[10px] font-black uppercase tracking-widest ring-1 ring-inset
                                                     @if($estimate->status === 'accepted') bg-emerald-50 text-emerald-700 ring-emerald-600/10
                                                     @elseif($estimate->status === 'declined') bg-rose-50 text-rose-700 ring-rose-600/10
                                                     @elseif($estimate->status === 'sent') bg-sky-50 text-sky-700 ring-sky-600/10
                                                     @elseif($estimate->status === 'waiting_approval') bg-amber-50 text-amber-700 ring-amber-600/10
                                                     @else bg-slate-100 text-slate-600 ring-slate-400/10
                                                     @endif transition-all group-hover:shadow-sm">
                                            <span class="h-1.5 w-1.5 rounded-full mr-2 
                                                @if($estimate->status === 'accepted') bg-emerald-500 
                                                @elseif($estimate->status === 'declined') bg-rose-500
                                                @elseif($estimate->status === 'sent') bg-sky-500
                                                @elseif($estimate->status === 'waiting_approval') bg-amber-500
                                                @else bg-slate-400
                                                @endif"></span>
                                            {{ str_replace('_', ' ', $estimate->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-8 py-6 text-right">
                                        <div class="text-base font-black text-slate-900 group-hover:scale-105 transition-transform duration-300">
                                            {{ $estimate->currency_symbol }}<span class="text-slate-900">{{ number_format($estimate->grand_total, 2) }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-8 py-6 text-right">
                                        <div class="flex items-center justify-end opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-4 group-hover:translate-x-0">
                                            <a href="{{ route('estimates.show', $estimate) }}" onclick="event.stopPropagation()"
                                                class="h-10 w-10 flex items-center justify-center text-slate-400 bg-white shadow-lg shadow-slate-200 hover:text-indigo-600 rounded-xl transition-all" title="View Engagement">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center max-w-sm mx-auto">
                                            <div class="h-16 w-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-6 ring-1 ring-slate-100">
                                                <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                            </div>
                                            <h3 class="text-xl font-black text-slate-900">Zero Engagement</h3>
                                            <p class="mt-2 text-xs font-bold text-slate-500 leading-relaxed uppercase tracking-widest">Historical trace for "{{ $client->name }}" is empty.</p>
                                            <div class="mt-8">
                                                <a href="{{ route('estimates.create', ['client_id' => $client->id]) }}"
                                                    class="inline-flex items-center rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                                                    Initiate Proposal
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        .shadow-soft { shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); }
    </style>
</x-app-layout>