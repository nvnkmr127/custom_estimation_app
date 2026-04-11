@forelse($clients as $client)
    <tr class="group hover:bg-slate-50/50 transition-all duration-200 cursor-pointer" onclick="window.location='{{ route('clients.show', $client) }}'">
        <td class="whitespace-nowrap px-8 py-3.5">
            <div class="flex items-center">
                <div class="relative h-10 w-10 flex-shrink-0">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-black text-sm shadow-lg shadow-indigo-100 group-hover:scale-110 transition-transform duration-300">
                        {{ substr($client->name, 0, 1) }}
                    </div>
                    <div class="absolute -bottom-1 -right-1 h-3.5 w-3.5 bg-white rounded-full p-0.5 shadow-sm ring-1 ring-slate-100">
                        <div class="h-full w-full rounded-full bg-emerald-400 @if($client->estimates_count == 0) bg-slate-300 @endif"></div>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                        {{ $client->name }}
                    </div>
                    @if($client->city || $client->state)
                        <div class="text-[10px] text-slate-500 mt-0.5 flex items-center font-bold">
                            <svg class="mr-1 h-3 w-3 text-slate-400 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $client->city }}{{ $client->city && $client->state ? ', ' : '' }}{{ $client->state }}
                        </div>
                    @endif
                </div>
            </div>
        </td>
        <td class="whitespace-nowrap px-8 py-3.5">
            <div class="text-[12px] text-slate-700 font-bold bg-slate-100/50 px-2.5 py-1 rounded-lg inline-block group-hover:bg-white group-hover:shadow-sm transition-all">
                {{ $client->company ?? '—' }}
            </div>
        </td>
        <td class="px-8 py-3.5">
            <div class="flex flex-col gap-1.5">
                @if($client->email)
                    <div class="text-[11px] text-slate-600 flex items-center bg-slate-50/50 px-2 py-0.5 rounded-md w-fit group-hover:bg-white transition-all">
                        <svg class="mr-1.5 h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="font-bold limit-text-150">{{ $client->email }}</span>
                    </div>
                @endif
                @if($client->phone)
                    <div class="text-[11px] text-slate-600 flex items-center bg-slate-50/50 px-2 py-0.5 rounded-md w-fit group-hover:bg-white transition-all">
                        <svg class="mr-1.5 h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span class="font-bold">{{ $client->phone }}</span>
                    </div>
                @endif
            </div>
        </td>
        <td class="whitespace-nowrap px-8 py-3.5 text-center">
            <div class="inline-flex flex-col items-center">
                <span class="text-lg font-black text-slate-900 leading-none">{{ $client->estimates_count }}</span>
                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mt-1">Estimates</span>
            </div>
        </td>
        <td class="whitespace-nowrap px-8 py-3.5 text-right">
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-4 group-hover:translate-x-0">
                @can('update', $client)
                <a href="{{ route('clients.edit', $client) }}" onclick="event.stopPropagation()"
                    class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-white hover:shadow-lg rounded-xl transition-all" title="Edit Client">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>
                @endcan
                @can('delete', $client)
                <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="event.stopPropagation(); return confirm('Delete this client?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-white hover:shadow-lg rounded-xl transition-all"
                        title="Delete Client">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-1 0l-.8 12.1a2 2 0 01-2 1.9H9.8a2 2 0 01-2-1.9L7 7m3 0V5a2 2 0 012-2h2a2 2 0 012 2v2" />
                        </svg>
                    </button>
                </form>
                @endcan
                <div class="h-6 w-px bg-slate-200 mx-0.5"></div>
                <a href="{{ route('clients.show', $client) }}" onclick="event.stopPropagation()"
                    class="p-2 text-white bg-slate-900 hover:bg-indigo-600 shadow-lg rounded-xl transition-all" title="Full Report">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-8 py-24 text-center">
            <div class="flex flex-col items-center max-w-sm mx-auto">
                <div class="h-24 w-24 rounded-[2rem] bg-slate-50 flex items-center justify-center mb-8 ring-1 ring-slate-100">
                    <svg class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900">Portfolio is quiet</h3>
                <p class="mt-3 text-slate-500 leading-relaxed font-medium">Capture your first client record to begin tracking business value and engagements.</p>
                <div class="mt-10 flex gap-4">
                    @if(request()->anyFilled(['search', 'city', 'has_estimates']))
                        <a href="{{ route('clients.index') }}"
                            class="inline-flex items-center rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50">
                            Clear Search Criteria
                        </a>
                    @endif
                    <a href="{{ route('clients.create') }}"
                        class="inline-flex items-center rounded-2xl bg-indigo-600 px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-100 hover:bg-indigo-700">
                        Add First Client
                    </a>
                </div>
            </div>
        </td>
    </tr>
@endforelse
