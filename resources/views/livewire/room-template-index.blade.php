<div class="min-h-screen pb-12">
    <!-- Header Section -->
    <div class="mb-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Room Templates</h1>
                <p class="mt-2 text-slate-500 font-medium">Create and manage reusable room configurations for your
                    estimates.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('templates.create') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold shadow-sm hover:bg-slate-800 transition-all active:scale-95 group">
                    <svg class="mr-2 h-5 w-5 text-indigo-400 group-hover:rotate-90 transition-transform duration-300"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    New Template
                </a>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white/60 backdrop-blur-md sticky top-4 z-30 p-4 rounded-2xl border border-slate-200 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row gap-4 items-center">
            <!-- Search Input -->
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/30 py-3 pl-11 pr-4 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all sm:text-sm"
                    placeholder="Search templates by name or description...">
                <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <svg class="animate-spin h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- View Mode Toggle -->
                <div class="flex items-center p-1 bg-slate-50 rounded-xl border border-slate-200 shadow-inner">
                    <button wire:click="setViewMode('table')"
                        class="p-2 rounded-lg transition-all {{ $viewMode === 'table' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-400 hover:text-slate-600' }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button wire:click="setViewMode('grid')"
                        class="p-2 rounded-lg transition-all {{ $viewMode === 'grid' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-400 hover:text-slate-600' }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                </div>

                @if($search)
                    <button wire:click="$set('search', '')"
                        class="text-xs font-black text-rose-500 hover:text-rose-700 uppercase tracking-widest px-2">
                        Reset
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Listings -->
    <div class="relative">
        <div wire:loading.delay.longest class="absolute inset-x-0 -top-1 z-50">
            <div class="h-1 w-full bg-slate-100 overflow-hidden rounded-full">
                <div class="h-full bg-slate-900 animate-progress origin-left-right"></div>
            </div>
        </div>

        @if($templates->count() > 0)
            @if($viewMode === 'table')
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Template Name</th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Items</th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Description</th>
                                    <th
                                        class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                @foreach($templates as $template)
                                    <tr wire:key="row-{{ $template->id }}"
                                        class="hover:bg-slate-50/50 transition-all group border-b border-slate-50 last:border-0">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold group-hover:scale-110 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                                                    {{ substr($template->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <a href="{{ route('templates.edit', $template) }}"
                                                        class="text-sm font-bold text-slate-900 leading-tight hover:text-slate-600 transition-colors">
                                                        {{ $template->name }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200/50">
                                                {{ count($template->items ?? []) }} items
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm text-slate-500 line-clamp-1">{{ $template->description ?: '-' }}</p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('templates.show', $template) }}"
                                                    class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-all">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('templates.edit', $template) }}"
                                                    class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-all">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                                <button wire:click="deleteTemplate({{ $template->id }})"
                                                    wire:confirm="Are you sure you want to delete this template?"
                                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- Grid View -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($templates as $template)
                        <div wire:key="card-{{ $template->id }}"
                            class="group bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:border-slate-400 transition-all duration-300 flex flex-col">
                            <div class="p-6 flex-1">
                                <div class="flex items-start justify-between mb-4">
                                    <div
                                        class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-500 text-xl font-black shadow-inner group-hover:bg-slate-900 group-hover:text-white transition-all duration-300">
                                        {{ substr($template->name, 0, 1) }}
                                    </div>
                                    <div class="flex gap-1">
                                        <a href="{{ route('templates.edit', $template) }}"
                                            class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-50 rounded-lg transition-all">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                <h3
                                    class="text-lg font-black text-slate-900 mb-2 leading-tight group-hover:text-slate-600 transition-colors">
                                    {{ $template->name }}
                                </h3>
                                <p class="text-sm text-slate-500 line-clamp-2 mb-4">
                                    {{ $template->description ?: 'No description provided for this template.' }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 border border-slate-200/50">
                                        {{ count($template->items ?? []) }} Items
                                    </span>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-slate-50/10 border-t border-slate-100 flex items-center justify-between">
                                <a href="{{ route('templates.show', $template) }}"
                                    class="text-xs font-black text-slate-500 hover:text-slate-900 transition-colors uppercase tracking-widest">
                                    Preview &rarr;
                                </a>
                                <button wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Are you sure?"
                                    class="text-[10px] font-black text-rose-400 hover:text-rose-600 uppercase tracking-widest transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-10 px-6 py-4 bg-white/50 backdrop-blur-sm rounded-2xl border border-slate-200">
                {{ $templates->links() }}
            </div>
        @else
            <div class="px-6 py-20 text-center bg-white rounded-3xl border border-dotted border-slate-300 shadow-inner">
                <div
                    class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-300 mb-6 border border-slate-100">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-900">No templates found</h3>
                <p class="mt-2 text-slate-500 max-w-sm mx-auto font-medium">Try adjusting your filters or search terms to
                    find what you're looking for.</p>
                @if($search)
                    <button wire:click="$set('search', '')"
                        class="mt-6 px-6 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800 transition-all shadow-lg active:scale-95">
                        Clear all filters
                    </button>
                @endif
            </div>
        @endif
    </div>

    <style>
        @keyframes progress {
            0% {
                transform: translateX(-100%) scaleX(0.2);
            }

            50% {
                transform: translateX(0) scaleX(0.5);
            }

            100% {
                transform: translateX(100%) scaleX(0.2);
            }
        }

        .animate-progress {
            animation: progress 2s infinite ease-in-out;
        }
    </style>
</div>