<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">

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
                    Webhook Catchers
                </span>
                <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight drop-shadow-sm">
                    Inbound Traffic Control
                </h1>
                <p class="mt-4 text-lg text-indigo-100 max-w-xl font-medium leading-relaxed">
                    Create unique, secure URLs to capture incoming events from third-party services. Map payloads to
                    system actions instantly.
                </p>
            </div>
            <div class="flex-shrink-0">
                <button wire:click="$set('showCreateModal', true)"
                    class="group relative inline-flex items-center gap-3 overflow-hidden rounded-2xl bg-white px-6 py-3 font-bold text-indigo-600 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-indigo-500/30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">
                    <span
                        class="absolute inset-0 bg-gradient-to-r from-teal-50 to-indigo-50 opacity-0 transition-opacity group-hover:opacity-100"></span>
                    <span class="relative flex items-center gap-2">
                        <svg class="h-5 w-5 transition-transform duration-300 group-hover:-rotate-12" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Endpoint
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Endpoints Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($endpoints as $endpoint)
            <div
                class="group relative overflow-hidden rounded-[2rem] bg-white p-6 shadow-sm hover:shadow-xl transition-all duration-300 ring-1 ring-slate-100 hover:ring-indigo-500/50">
                <div class="absolute top-0 right-0 p-6 opacity-0 group-hover:opacity-10 transition-opacity text-indigo-600">
                    <svg class="w-24 h-24 transform translate-x-6 -translate-y-6 rotate-12" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V9h-2.83v9.09L9 16.5l-1.41 1.41L12 22.33l4.41-4.42-1.41-1.41-1.59 1.59z" />
                    </svg>
                </div>

                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                    {{ $endpoint->name }}
                                </h3>
                                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                                    {{ $endpoint->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button wire:click="manageMappers({{ $endpoint->id }})"
                                class="p-2 rounded-lg bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all border border-slate-100"
                                title="Data Mappers">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                    </path>
                                </svg>
                            </button>
                            <button wire:click="editSecurity({{ $endpoint->id }})"
                                class="p-2 rounded-lg bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all border border-slate-100"
                                title="Security Settings">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </button>
                            <button wire:click="edit({{ $endpoint->id }})"
                                class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100"
                                title="Edit Endpoint">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                            <button wire:click="toggleActive({{ $endpoint->id }})"
                                class="p-1.5 rounded-lg border border-slate-100 bg-slate-50 hover:bg-slate-100 transition shadow-sm"
                                title="Toggle Active">
                                <div
                                    class="w-8 h-4 rounded-full relative {{ $endpoint->is_active ? 'bg-teal-500' : 'bg-slate-300' }} transition-colors">
                                    <div
                                        class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform {{ $endpoint->is_active ? 'translate-x-4' : 'translate-x-0' }}">
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>


                    @if($endpoint->description)
                        <p class="text-sm text-slate-500 mb-4 line-clamp-2">{{ $endpoint->description }}</p>
                    @endif

                    {{-- Validated Badge --}}
                    @if($endpoint->secret)
                        <div
                            class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-emerald-600/10">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Secured
                        </div>
                    @endif

                    <div
                        class="bg-slate-50 rounded-xl p-3 border border-slate-100 group-hover:border-indigo-100 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <code
                                class="text-xs font-mono text-slate-600 truncate flex-1">{{ route('webhooks.catch', $endpoint->uuid) }}</code>
                            <button
                                onclick="navigator.clipboard.writeText('{{ route('webhooks.catch', $endpoint->uuid) }}');"
                                class="flex-none p-1.5 rounded-lg bg-white shadow-sm text-slate-500 hover:text-indigo-600 hover:scale-105 transition-all active:scale-95"
                                title="Copy URL">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                             <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold {{ $endpoint->is_active ? 'bg-teal-50 text-teal-700 ring-1 ring-teal-600/10' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-500/10' }}">
                                <span
                                    class="h-1.5 w-1.5 rounded-full {{ $endpoint->is_active ? 'bg-teal-500' : 'bg-slate-400' }}"></span>
                                {{ $endpoint->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <button wire:click="delete({{ $endpoint->id }})"
                                class="p-1 px-2 rounded-md hover:bg-rose-50 text-slate-300 hover:text-rose-500 transition flex items-center gap-1 text-[10px] uppercase font-bold tracking-wider"
                                title="Delete Endpoint">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                Delete
                            </button>
                        </div>

                        <button wire:click="$set('selectedEndpointUuid', '{{ $endpoint->uuid }}')"
                            class="text-xs font-bold text-indigo-600 hover:text-indigo-500 flex items-center gap-1 hover:gap-2 transition-all">
                            View Logs <span aria-hidden="true">&rarr;</span>
                        </button>
                    </div>

                </div>
            </div>
        @empty
            <div
                class="col-span-full text-center py-20 bg-slate-50/50 rounded-[2rem] border-2 border-dashed border-slate-200">
                <div class="mx-auto h-24 w-24 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <p class="text-slate-500 font-medium mb-1">No webhook catchers configured.</p>
                <p class="text-slate-400 text-sm mb-6 max-w-sm mx-auto">Get started by creating your first endpoint to start
                    listening for external events.</p>
                <button wire:click="$set('showCreateModal', true)"
                    class="inline-flex items-center font-bold text-indigo-600 hover:text-indigo-500 hover:underline">
                    Create Endpoint &rarr;
                </button>
            </div>
        @endforelse
    </div>

    {{-- Live Feed Section --}}
    <div class="rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div
            class="px-8 py-6 border-b border-slate-50 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-3">
                    Inbound Traffic
                    @if($selectedEndpointUuid)
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                            Filtered: {{ Str::limit($selectedEndpointUuid, 8) }}
                            <button wire:click="$set('selectedEndpointUuid', null)"
                                class="ml-1 text-indigo-400 hover:text-indigo-700">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </span>
                    @endif
                </h2>
                <p class="text-sm text-slate-500 mt-1">Real-time log of received webhook events.</p>
            </div>

            <button wire:click="$refresh"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 shadow-sm text-sm font-bold text-slate-600 hover:text-indigo-600 hover:border-indigo-100 transition-all">
                <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Refresh Feed
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-white">
                    <tr>
                        <th scope="col"
                            class="px-8 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Time
                        </th>
                        <th scope="col"
                            class="px-8 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Source
                        </th>
                        <th scope="col"
                            class="px-8 py-4 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Payload Snippet</th>
                        <th scope="col"
                            class="px-8 py-4 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">
                            Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    @forelse($recentEvents as $event)
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            <td class="px-8 py-5 whitespace-nowrap">
                                <div class="text-sm font-bold text-slate-900">{{ $event->created_at->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-slate-500">{{ $event->created_at->format('M d') }}</div>
                            </td>
                            <td class="px-8 py-5 text-sm">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">POST</span>
                                    <span class="font-mono text-xs text-slate-500"
                                        title="{{ $event->provider}}">{{ Str::limit($event->provider, 12, '...') }}</span>
                                </div>
                                <div class="mt-1 text-xs text-slate-400 font-mono truncate max-w-[150px]"
                                    title="ID: {{ $event->provider_event_id }}">
                                    #{{ $event->provider_event_id }}
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div
                                    class="bg-slate-900 rounded-lg p-3 max-w-lg shadow-inner ring-1 ring-white/10 group-hover:ring-indigo-500/20 transition-all">
                                    <code class="text-xs text-indigo-300 font-mono block truncate opacity-90">
                                            {{ Str::limit(json_encode($event->payload), 80) }}
                                        </code>
                                </div>
                                @if($event->error_message)
                                    <div class="mt-1 text-xs text-rose-500 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Processing Failed
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-right text-sm">
                                <button wire:click="inspectEvent({{ $event->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline">Inspect</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="font-medium">No events captured yet.</p>
                                    <p class="text-xs text-slate-400 mt-1">Events sent to your catchers will appear here
                                        instantly.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recentEvents->hasPages())
            <div class="px-8 py-4 border-t border-slate-100 bg-slate-50/30">
                {{ $recentEvents->links() }}
            </div>
        @endif
    </div>

    {{-- Inspector Slide-over --}}
    @if($showInspector && $selectedEvent)
        <div class="relative z-50 animate-in fade-in duration-300" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" wire:click="closeInspector">
            </div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div
                            class="pointer-events-auto w-screen max-w-2xl transform transition ease-in-out duration-500 sm:duration-700 animate-in slide-in-from-right">
                            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-2xl">
                                <div class="px-4 py-6 sm:px-6 bg-slate-50 border-b border-slate-200">
                                    <div class="flex items-start justify-between">
                                        <h2 class="text-lg font-bold text-slate-900 leading-6">Event Details</h2>
                                        <div class="ml-3 flex h-7 items-center">
                                            <button wire:click="closeInspector" type="button"
                                                class="relative rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 ring-1 ring-slate-100 shadow-sm p-1">
                                                <span class="sr-only">Close panel</span>
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-1">
                                        <p class="text-sm text-slate-500 font-mono">ID:
                                            {{ $selectedEvent->provider_event_id }}
                                        </p>
                                    </div>
                                </div>
                                <div class="relative flex-1 px-4 py-6 sm:px-6 space-y-8">
                                    {{-- Status Card --}}
                                    <div
                                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                                Processing Status</div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-bold {{ $selectedEvent->status === 'processed' ? 'bg-emerald-100 text-emerald-800' : ($selectedEvent->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                    {{ ucfirst($selectedEvent->status) }}
                                                </span>
                                                @if($selectedEvent->attempt_count > 0)
                                                    <span class="text-xs text-slate-400">Attempt
                                                        #{{ $selectedEvent->attempt_count }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <button wire:click="replayEvent({{ $selectedEvent->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-xs font-bold text-indigo-600 shadow-sm ring-1 ring-slate-200 hover:bg-indigo-50 transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Replay
                                        </button>
                                    </div>

                                    {{-- Headers --}}
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 mb-3">Response Headers</h3>
                                        <div
                                            class="bg-slate-900 rounded-xl overflow-hidden shadow-inner ring-1 ring-white/10">
                                            <div class="overflow-x-auto p-4">
                                                <pre
                                                    class="text-xs font-mono text-indigo-200 leading-relaxed max-h-48 overflow-y-auto custom-scrollbar">{{ json_encode($selectedEvent->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Payload --}}
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 mb-3">Payload Body</h3>
                                        <div
                                            class="bg-slate-900 rounded-xl overflow-hidden shadow-inner ring-1 ring-white/10">
                                            <div class="overflow-x-auto p-4">
                                                <pre
                                                    class="text-xs font-mono text-emerald-300 leading-relaxed max-h-96 overflow-y-auto custom-scrollbar">{{ json_encode($selectedEvent->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Security Modal --}}
    @if($showSecurityModal)
        <div class="relative z-50 animate-in fade-in duration-300" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-in zoom-in-95 duration-300">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-bold leading-6 text-slate-900">Endpoint Security</h3>
                                    <p class="text-sm text-slate-500 mt-1">Configure verification rules to secure your
                                        webhook endpoint.</p>

                                    <div class="mt-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-bold leading-6 text-slate-900">Secret
                                                Key</label>
                                            <div
                                                class="mt-1 flex rounded-md shadow-sm ring-1 ring-inset ring-slate-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600">
                                                <input type="text" wire:model="securitySecret"
                                                    class="block flex-1 border-0 bg-transparent py-2.5 pl-3 text-slate-900 placeholder:text-slate-400 focus:ring-0 sm:text-sm sm:leading-6"
                                                    placeholder="e.g. whsec_...">
                                                <button type="button"
                                                    wire:click="$set('securitySecret', '{{ Str::random(32) }}')"
                                                    class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-md px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 border-l border-slate-300">
                                                    Generate
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold leading-6 text-slate-900">Signature
                                                Header</label>
                                            <input type="text" wire:model="securitySignatureHeader"
                                                class="mt-1 block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                placeholder="e.g. X-Hub-Signature-256">
                                            <p class="mt-1 text-xs text-slate-500">The header key containing the HMRC
                                                signature.</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold leading-6 text-slate-900">Allowed
                                                IPs</label>
                                            <textarea wire:model="securityIpWhitelist" rows="3"
                                                class="mt-1 block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                placeholder="192.168.1.1, 10.0.0.1"></textarea>
                                            <p class="mt-1 text-xs text-slate-500">Comma-separated list of allowed IP
                                                addresses. Leave blank to allow all.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" wire:click="saveSecurity"
                                class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 sm:ml-3 sm:w-auto">Save
                                Configuration</button>
                            <button type="button" wire:click="$set('showSecurityModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Mapper Modal --}}
    @if($showMapperModal)
        <div class="relative z-50 animate-in fade-in duration-300" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl animate-in zoom-in-95 duration-300">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-bold leading-6 text-slate-900">Data Transformers</h3>
                                    <p class="text-sm text-slate-500 mt-1">Map incoming webhook data to system actions.</p>

                                    {{-- Existing Mappers List --}}
                                    <div class="mt-6">
                                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Active
                                            Mappers</h4>
                                        <div class="space-y-3">
                                            @forelse($mappers as $mapper)
                                                <div
                                                    class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-sm">{{ $mapper->name }}</div>
                                                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                                                            {{ $mapper->action_type }}</div>
                                                    </div>
                                                    <button wire:click="deleteMapper({{ $mapper->id }})"
                                                        class="text-slate-400 hover:text-rose-500 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @empty
                                                <div
                                                    class="text-center py-6 border-2 border-dashed border-slate-100 rounded-xl">
                                                    <p class="text-sm text-slate-400">No mappers configured yet.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- New Mapper Form --}}
                                    <div class="mt-8 pt-6 border-t border-slate-100">
                                        <h4 class="text-sm font-bold text-slate-900 mb-4">Add New Mapper</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">Rule Name</label>
                                                <input type="text" wire:model="newMapperName"
                                                    class="block w-full rounded-lg border-0 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    placeholder="e.g. Create Lead from Typeform">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-700 mb-1">Action
                                                        Type</label>
                                                    <select wire:model="newMapperActionType"
                                                        class="block w-full rounded-lg border-0 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                        <option value="create_lead">Create Lead</option>
                                                        <option value="update_estimate">Update Estimate</option>
                                                        <option value="notify_slack">Slack Notification</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-700 mb-1">Trigger
                                                        (JSON)</label>
                                                    <input type="text" wire:model="newMapperTriggerRules"
                                                        class="block w-full rounded-lg border-0 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        placeholder='{"field": "type", "value": "submit"}'>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <label class="block text-xs font-bold text-slate-700">Field
                                                        Mapping</label>
                                                    <button type="button" wire:click="discoverKeys"
                                                        class="text-xs text-indigo-600 hover:text-indigo-500 font-bold flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                            </path>
                                                        </svg>
                                                        Auto-Discover Keys
                                                    </button>
                                                </div>

                                                @if(session()->has('warning'))
                                                    <div
                                                        class="mb-2 text-xs text-amber-600 bg-amber-50 p-2 rounded border border-amber-200">
                                                        {{ session('warning') }}
                                                    </div>
                                                @endif
                                                @if(session()->has('success'))
                                                    <div
                                                        class="mb-2 text-xs text-emerald-600 bg-emerald-50 p-2 rounded border border-emerald-200">
                                                        {{ session('success') }}
                                                    </div>
                                                @endif

                                                <div class="border rounded-xl overflow-hidden ring-1 ring-slate-200">
                                                    <table class="min-w-full divide-y divide-slate-100">
                                                        <thead class="bg-slate-50">
                                                            <tr>
                                                                <th scope="col"
                                                                    class="px-4 py-2 text-left text-xs font-bold text-slate-500 uppercase">
                                                                    Incoming Field (Payload)</th>
                                                                <th scope="col"
                                                                    class="px-4 py-2 text-left text-xs font-bold text-slate-500 uppercase">
                                                                    System Field</th>
                                                                <th scope="col" class="px-4 py-2 text-right"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-slate-100">
                                                            @foreach($mappingRows as $index => $row)
                                                                <tr>
                                                                    <td class="px-4 py-2">
                                                                        @if(count($availablePayloadKeys) > 0)
                                                                            <select wire:model="mappingRows.{{ $index }}.source"
                                                                                class="block w-full rounded-lg border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6">
                                                                                <option value="">Select Payload Key</option>
                                                                                @foreach($availablePayloadKeys as $key)
                                                                                    <option value="{{ $key }}">{{ $key }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @else
                                                                            <input type="text"
                                                                                wire:model="mappingRows.{{ $index }}.source"
                                                                                class="block w-full rounded-lg border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6"
                                                                                placeholder="e.g. data.email">
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-4 py-2">
                                                                        <select
                                                                            wire:model="mappingRows.{{ $index }}.destination"
                                                                            class="block w-full rounded-lg border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-6">
                                                                            <option value="">Select System Field</option>
                                                                            @foreach($systemFields as $fieldKey => $fieldLabel)
                                                                                <option value="{{ $fieldKey }}">{{ $fieldLabel }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td class="px-4 py-2 text-right">
                                                                        <button type="button"
                                                                            wire:click="removeMappingRow({{ $index }})"
                                                                            class="text-slate-400 hover:text-rose-500">
                                                                            <svg class="w-4 h-4" fill="none"
                                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round" stroke-width="2"
                                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                                </path>
                                                                            </svg>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                    <div class="bg-slate-50 px-4 py-2 border-t border-slate-100">
                                                        <button type="button" wire:click="addMappingRow"
                                                            class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                            Add Field Map
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex gap-3">
                                                <button wire:click="dryRun" type="button" class="flex-1 rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-200 ring-1 ring-slate-200">
                                                    Test Logic (Dry Run)
                                                </button>
                                                <button wire:click="createMapper" class="flex-[2] rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                    Save Mapper Rule
                                                </button>
                                            </div>

                                            @if($dryRunResult)
                                                <div class="mt-4 p-4 rounded-xl border {{ $dryRunResult['type'] === 'success' ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200' }}">
                                                    <div class="flex items-start gap-3">
                                                        @if($dryRunResult['type'] === 'success')
                                                            <svg class="w-5 h-5 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        @else
                                                            <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                        @endif
                                                        <div>
                                                            <h5 class="text-sm font-bold {{ $dryRunResult['type'] === 'success' ? 'text-emerald-900' : 'text-amber-900' }}">
                                                                {{ $dryRunResult['type'] === 'success' ? 'Test Passed' : 'Test Failed' }}
                                                            </h5>
                                                            <p class="text-xs mt-1 {{ $dryRunResult['type'] === 'success' ? 'text-emerald-700' : 'text-amber-700' }}">
                                                                {{ $dryRunResult['message'] }}
                                                            </p>
                                                            @if(isset($dryRunResult['data']))
                                                                <div class="mt-3 bg-white/50 rounded-lg p-2 font-mono text-xs text-slate-700 border border-black/5">
                                                                    <pre>{{ json_encode($dryRunResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" wire:click="$set('showMapperModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif


    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="relative z-50 animate-in fade-in duration-300" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-in zoom-in-95 duration-300">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">Create New
                                        Catcher</h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="name"
                                                class="block text-sm font-bold leading-6 text-slate-900">Friendly
                                                Name</label>
                                            <div class="mt-1">
                                                <input type="text" wire:model="newName" id="name"
                                                    class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    placeholder="e.g. Typeform Responses">
                                            </div>
                                            @error('newName') <span
                                                class="text-rose-500 text-xs mt-1 block font-medium">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="description"
                                                class="block text-sm font-bold leading-6 text-slate-900">Description <span
                                                    class="text-slate-400 font-normal">(Optional)</span></label>
                                            <div class="mt-1">
                                                <textarea wire:model="newDescription" id="description" rows="3"
                                                    class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    placeholder="What is this endpoint capturing?"></textarea>
                                            </div>
                                            @error('newDescription') <span
                                                class="text-rose-500 text-xs mt-1 block font-medium">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" wire:click="create"
                                class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto ring-1 ring-indigo-600">Create</button>
                            <button type="button" wire:click="$set('showCreateModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="relative z-50 animate-in fade-in duration-300" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-in zoom-in-95 duration-300">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">Edit Endpoint</h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="edit_name"
                                                class="block text-sm font-bold leading-6 text-slate-900">Friendly
                                                Name</label>
                                            <div class="mt-1">
                                                <input type="text" wire:model="editName" id="edit_name"
                                                    class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            @error('editName') <span
                                                class="text-rose-500 text-xs mt-1 block font-medium">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="edit_description"
                                                class="block text-sm font-bold leading-6 text-slate-900">Description <span
                                                    class="text-slate-400 font-normal">(Optional)</span></label>
                                            <div class="mt-1">
                                                <textarea wire:model="editDescription" id="edit_description" rows="3"
                                                    class="block w-full rounded-xl border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                                            </div>
                                            @error('editDescription') <span
                                                class="text-rose-500 text-xs mt-1 block font-medium">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" wire:click="update"
                                class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto ring-1 ring-indigo-600">Save Changes</button>
                            <button type="button" wire:click="$set('showEditModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>