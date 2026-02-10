<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">

    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-600/10">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-emerald-800">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Hero Section: Mesh Gradient --}}
    <div class="relative overflow-hidden rounded-[2.5rem] bg-indigo-600 shadow-2xl ring-1 ring-white/20">
        {{-- Abstract Decorative Elements --}}
        <div
            class="absolute -top-24 -left-20 h-96 w-96 rounded-full bg-purple-500 blur-3xl opacity-50 mix-blend-multiply animate-pulse">
        </div>
        <div class="absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-teal-400 blur-3xl opacity-50 mix-blend-multiply animate-pulse"
            style="animation-delay: 2s;"></div>
        <div
            class="absolute inset-0 bg-gradient-to-br from-indigo-600/90 via-violet-600/90 to-fuchsia-600/90 backdrop-blur-sm">
        </div>

        {{-- Mesh Noise Overlay --}}
        <div class="absolute inset-0 opacity-20"
            style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E&quot;);">
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between px-8 py-10 gap-6">
            <div>
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80 ring-1 ring-white/20 mb-4 backdrop-blur-md">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-400 animate-pulse"></span>
                    Outbound Logic
                </span>
                <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight drop-shadow-sm">
                    Webhook Dispatcher
                </h1>
                <p class="mt-4 text-lg text-indigo-100 max-w-xl font-medium leading-relaxed">
                    Broadcast system events to your external services. Manage endpoints, monitor health, and debug
                    delivery issues.
                </p>
            </div>
            <div class="flex flex-shrink-0 gap-3">
                <a href="{{ route('admin.webhooks.catchers.index') }}"
                    class="group relative inline-flex items-center gap-3 overflow-hidden rounded-2xl bg-white/10 px-6 py-3 font-bold text-white shadow-xl transition-all duration-300 hover:bg-white/20 ring-1 ring-white/20">
                    Inbound Catchers
                </a>
                <a href="{{ route('admin.webhooks.create') }}"
                    class="group relative inline-flex items-center gap-3 overflow-hidden rounded-2xl bg-white px-6 py-3 font-bold text-indigo-600 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-indigo-500/30 focus:outline-none focus:ring-2 focus:ring-white">
                    <span
                        class="absolute inset-0 bg-gradient-to-r from-teal-50 to-indigo-50 opacity-0 transition-opacity group-hover:opacity-100"></span>
                    <span class="relative flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Endpoint
                    </span>
                </a>
            </div>
        </div>
    </div>

    {{-- Filters & Content --}}
    <div class="rounded-[2.5rem] bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div
            class="px-8 py-6 border-b border-slate-50 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-1 gap-4 max-w-2xl">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search endpoints..."
                        class="block w-full pl-10 pr-3 py-2.5 border-0 rounded-xl bg-white ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-600 sm:text-sm transition-all hover:ring-slate-300">
                </div>
                <select wire:model.live="status"
                    class="block rounded-xl border-0 py-2.5 px-4 bg-white ring-1 ring-slate-200 text-slate-600 focus:ring-2 focus:ring-indigo-600 sm:text-sm transition-all hover:ring-slate-300">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Disabled</option>
                    <option value="disabled_by_system">Circuit Open</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-white">
                    <tr>
                        <th scope="col"
                            class="px-8 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors"
                            wire:click="sortBy('name')">
                            <div class="flex items-center gap-2">
                                Endpoint
                                @if($sortField === 'name')
                                    <svg class="h-3 w-3 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }} transition-transform"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th scope="col"
                            class="px-8 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Target
                            URL</th>
                        <th scope="col"
                            class="px-8 py-4 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Health</th>
                        <th scope="col"
                            class="px-8 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    @forelse ($webhooks as $webhook)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div
                                            class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ $webhook->name }}</div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            @if($webhook->status === 'active')
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/10">
                                                    <span class="h-1 w-1 rounded-full bg-emerald-500"></span> Active
                                                </span>
                                            @elseif($webhook->status === 'inactive')
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-slate-600/10">
                                                    <span class="h-1 w-1 rounded-full bg-slate-400"></span> Disabled
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-600/10">
                                                    <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                                                    {{ ucfirst(str_replace('_', ' ', $webhook->status)) }}
                                                </span>
                                            @endif
                                            <span
                                                class="text-[10px] text-slate-400 font-medium">{{ count($webhook->events ?? []) }}
                                                event types</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="text-xs font-mono text-slate-500 bg-slate-50 rounded-lg px-2 py-1 inline-block">
                                    {{ Str::mask($webhook->url, '*', 8, -5) }}
                                </div>
                                @if($webhook->latestDelivery)
                                    <div class="mt-1 text-[10px] text-slate-400">
                                        Last: {{ $webhook->latestDelivery->created_at->diffForHumans() }}
                                        <span
                                            class="{{ $webhook->latestDelivery->status === 'success' ? 'text-emerald-500' : 'text-rose-500' }}">({{ ucfirst($webhook->latestDelivery->status) }})</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-center">
                                @php
                                    $rate = $webhook->success_rate;
                                    $color = $rate > 95 ? 'text-emerald-600 bg-emerald-50' : ($rate > 80 ? 'text-amber-600 bg-amber-50' : 'text-rose-600 bg-rose-50');
                                @endphp
                                <div class="inline-flex flex-col items-center">
                                    <div class="text-sm font-black {{ $color }} px-2 py-1 rounded-lg">
                                        {{ $rate }}%
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-wider">Success
                                        Rate</span>
                                </div>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="toggleStatus('{{ $webhook->id }}')"
                                        class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-indigo-600 transition-all shadow-sm bg-white ring-1 ring-slate-100"
                                        title="{{ $webhook->status === 'active' ? 'Disable' : 'Enable' }} Endpoint">
                                        @if($webhook->status === 'active')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </button>
                                    <a href="{{ route('admin.webhooks.logs.index', ['webhookId' => $webhook->id]) }}"
                                        class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-indigo-600 transition-all shadow-sm bg-white ring-1 ring-slate-100"
                                        title="View Logs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17v-2m3 2v-4m3 2v-6m-8 9h11a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.webhooks.edit', $webhook) }}"
                                        class="p-2 rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                                        title="Edit Endpoint">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button wire:click="delete('{{ $webhook->id }}')"
                                        wire:confirm="Are you sure you want to delete this webhook endpoint? This action cannot be undone."
                                        class="p-2 rounded-xl border border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                        title="Delete Endpoint">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-20 w-20 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                                        <svg class="h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <p class="font-bold text-slate-400 uppercase tracking-widest text-xs">No webhooks found
                                    </p>
                                    <a href="{{ route('admin.webhooks.create') }}"
                                        class="mt-4 text-indigo-600 font-bold hover:underline">Create your first endpoint
                                        &rarr;</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($webhooks->hasPages())
            <div class="px-8 py-4 border-t border-slate-50 bg-slate-50/30">
                {{ $webhooks->links() }}
            </div>
        @endif
    </div>
</div>