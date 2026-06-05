<div class="space-y-6">
    <!-- Header Area -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Plugins & Integrations</h1>
            <p class="mt-2 text-sm text-slate-600">Connect external applications, configure inbound/outbound sync channels, and toggle automation modules.</p>
        </div>
        <div class="mt-4 sm:ml-4 sm:mt-0">
            <button type="button" wire:click="openCreatePlugin" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm transition-all duration-200">
                <svg class="w-5 h-5 mr-1.5 -ml-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Custom Plugin
            </button>
        </div>
    </div>

    <!-- Notifications / Alerts -->
    @if (session()->has('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="selectTab('plugins')" class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 {{ $activeTab === 'plugins' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Installed Plugins
            </button>
            <button wire:click="selectTab('logs')" class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 {{ $activeTab === 'logs' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Activity Logs & Deliveries
            </button>
            <button wire:click="selectTab('guide')" class="border-b-2 py-4 px-1 text-sm font-semibold transition-colors duration-200 {{ $activeTab === 'guide' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                Developer Guide & KT
            </button>
        </nav>
    </div>

    @if ($activeTab === 'plugins')
        <!-- Plugins Tab -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plugins as $plugin)
                @php
                    // Dynamic premium theme colors based on plugin key
                    $colorTheme = match($plugin->key) {
                        'slack' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200/50', 'iconBg' => 'bg-amber-600 text-white', 'hover' => 'hover:shadow-amber-100/50'],
                        'quickbooks' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200/50', 'iconBg' => 'bg-emerald-600 text-white', 'hover' => 'hover:shadow-emerald-100/50'],
                        'hubspot' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200/50', 'iconBg' => 'bg-orange-600 text-white', 'hover' => 'hover:shadow-orange-100/50'],
                        default => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200/50', 'iconBg' => 'bg-indigo-600 text-white', 'hover' => 'hover:shadow-indigo-100/50']
                    };
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg {{ $colorTheme['hover'] }} flex flex-col justify-between overflow-hidden">
                    <div class="p-6 space-y-4">
                        <!-- Top Info -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center font-bold tracking-tight shadow-inner {{ $colorTheme['iconBg'] }}">
                                    @if ($plugin->key === 'slack')
                                        S
                                    @elseif ($plugin->key === 'quickbooks')
                                        Q
                                    @elseif ($plugin->key === 'hubspot')
                                        H
                                    @else
                                        {{ strtoupper(substr($plugin->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 leading-5">{{ $plugin->name }}</h3>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 mt-1">v{{ $plugin->version }}</span>
                                </div>
                            </div>
                            <!-- Status Toggle -->
                            <button type="button" wire:click="togglePluginStatus({{ $plugin->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $plugin->is_active ? 'bg-indigo-600' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $plugin->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>

                        <!-- Description -->
                        <p class="text-sm text-slate-500 leading-relaxed min-h-[40px]">{{ $plugin->description }}</p>

                        <!-- Module Stats badge -->
                        @if ($plugin->is_active)
                            <div class="flex items-center space-x-2 text-xs font-semibold text-indigo-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                <span>{{ $plugin->modules_count }} Active Connection Module(s)</span>
                            </div>
                        @else
                            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-400">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                <span>Disabled</span>
                            </div>
                        @endif
                    </div>

                    <!-- Actions Footer -->
                    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-between">
                        <button type="button" wire:click="openConfigPlugin({{ $plugin->id }})" class="text-sm font-semibold text-slate-600 hover:text-slate-900 flex items-center transition-colors duration-150">
                            <svg class="w-4 h-4 mr-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Configure API
                        </button>
                        <button type="button" wire:click="deletePlugin({{ $plugin->id }})" wire:confirm="Are you sure you want to delete this custom plugin?" class="text-xs text-rose-600 hover:text-rose-900 transition-colors duration-150">
                            Delete
                        </button>
                    </div>

                    <!-- Inner Modules List -->
                    @if ($plugin->is_active)
                        <div class="px-6 pb-6 bg-white border-t border-slate-100 pt-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Connection Modules</span>
                                <button type="button" wire:click="openCreateModule({{ $plugin->id }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                                    + Add Module
                                </button>
                            </div>
                            @if ($plugin->modules->isEmpty())
                                <p class="text-xs text-slate-400 italic">No modules added yet. Add one to connect another app!</p>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach ($plugin->modules as $mod)
                                        <div class="py-2.5 flex items-center justify-between group">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-semibold text-slate-800 truncate">{{ $mod->name }}</span>
                                                    <span class="inline-flex items-center rounded bg-slate-50 px-1.5 py-0.5 text-[9px] font-medium text-slate-600 border border-slate-200">{{ ucfirst($mod->type) }}</span>
                                                </div>
                                                @if ($mod->type === 'outbound')
                                                    <p class="text-[10px] text-slate-400 mt-0.5">Triggers on: <code class="text-slate-600">{{ $mod->event_name }}</code></p>
                                                @else
                                                    <div class="flex items-center space-x-1 mt-0.5" x-data="{ copied: false }">
                                                        <span class="text-[10px] text-slate-400 font-mono select-all truncate max-w-[140px]">{{ $mod->catch_url }}</span>
                                                        <button type="button" @click="navigator.clipboard.writeText('{{ $mod->catch_url }}'); copied = true; setTimeout(() => copied = false, 2000)" class="text-slate-400 hover:text-slate-700 focus:outline-none">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!copied">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                            </svg>
                                                            <span class="text-[8px] font-bold text-emerald-600" x-show="copied" style="display: none;">Copied!</span>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button type="button" wire:click="toggleModuleStatus({{ $mod->id }})" class="text-xs {{ $mod->is_active ? 'text-emerald-600 font-semibold' : 'text-slate-400' }} hover:underline">
                                                    {{ $mod->is_active ? 'Active' : 'Paused' }}
                                                </button>
                                                <button type="button" wire:click="openEditModule({{ $mod->id }})" class="text-slate-400 hover:text-slate-700 transition-colors">
                                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="deleteModule({{ $mod->id }})" wire:confirm="Delete this connection module?" class="text-slate-400 hover:text-rose-600 transition-colors">
                                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif ($activeTab === 'logs')
        <!-- Activity Logs Tab -->
        <div class="bg-white shadow-sm border border-slate-200/80 rounded-2xl overflow-hidden space-y-4 p-6">
            <!-- Search & Filters -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-center">
                <div class="sm:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="logSearch" placeholder="Search logs by error, body, or module..." class="block w-full rounded-lg border-0 py-2 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                    </div>
                </div>
                <div>
                    <select wire:model.live="logStatus" class="block w-full rounded-lg border-0 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div>
                    <select wire:model.live="logDirection" class="block w-full rounded-lg border-0 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                        <option value="">All Directions</option>
                        <option value="outbound">Outbound (Webhook call)</option>
                        <option value="inbound">Inbound (Catcher callback)</option>
                    </select>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Timestamp</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Plugin / Module</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Direction</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">HTTP Code</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Latency</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50/50 transition-colors duration-150">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-500">
                                    {{ $log->created_at->format('M d, H:i:s') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-indigo-600">{{ $log->module->plugin->name }}</span>
                                        <span class="text-slate-400 font-normal">/</span>
                                        <span>{{ $log->module->name }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    @if ($log->direction === 'inbound')
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/10">Inbound</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/10">Outbound</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if ($log->status === 'success')
                                        <span class="inline-flex items-center gap-x-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Success
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-x-1.5 rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-slate-600">
                                    {{ $log->response_code ?: 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                    {{ $log->latency_ms }} ms
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold">
                                    <button type="button" wire:click="showLogDetails({{ $log->id }})" class="text-indigo-600 hover:text-indigo-900 cursor-pointer">Inspect</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400 italic">No sync events logged. Verify your plugins are active and triggered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    @elseif ($activeTab === 'guide')
        <!-- Developer Guide & KT Tab -->
        <div class="bg-white/80 backdrop-blur-md shadow-lg border border-slate-200/80 rounded-2xl p-6 lg:p-8 space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-300">
            <!-- Header Banner -->
            <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl p-8 shadow-inner">
                <div class="absolute right-0 top-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
                <div class="absolute left-0 bottom-0 w-72 h-72 bg-emerald-500/10 rounded-full blur-2xl -ml-20 -mb-20"></div>
                <div class="relative z-10 max-w-2xl">
                    <span class="inline-flex items-center rounded-md bg-indigo-500/20 px-2.5 py-1 text-xs font-semibold text-indigo-200 border border-indigo-500/30">Developer Hub</span>
                    <h2 class="text-2xl lg:text-3xl font-extrabold mt-3 tracking-tight">Plugins & Connections Architecture</h2>
                    <p class="mt-2 text-slate-300 text-sm leading-relaxed">Learn how to extend the Estimation App, hook into core events, configure webhook mappers, and build Custom Plugins directly in the database.</p>
                </div>
            </div>

            <!-- Main Layout with Sidebar and Content -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8" x-data="{ guideSection: 'overview' }">
                <!-- Left Sidebar Nav -->
                <div class="space-y-1 lg:col-span-1">
                    <button @click="guideSection = 'overview'" :class="guideSection === 'overview' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'" class="w-full text-left border-l-4 px-4 py-3 text-sm font-semibold rounded-r-lg transition-all duration-150 flex items-center justify-between">
                        <span>1. Architecture Overview</span>
                        <svg class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button @click="guideSection = 'create-plugin'" :class="guideSection === 'create-plugin' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'" class="w-full text-left border-l-4 px-4 py-3 text-sm font-semibold rounded-r-lg transition-all duration-150 flex items-center justify-between">
                        <span>2. Creating Custom Plugins</span>
                        <svg class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button @click="guideSection = 'outbound-flows'" :class="guideSection === 'outbound-flows' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'" class="w-full text-left border-l-4 px-4 py-3 text-sm font-semibold rounded-r-lg transition-all duration-150 flex items-center justify-between">
                        <span>3. Outbound Payload Specs</span>
                        <svg class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button @click="guideSection = 'inbound-flows'" :class="guideSection === 'inbound-flows' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'" class="w-full text-left border-l-4 px-4 py-3 text-sm font-semibold rounded-r-lg transition-all duration-150 flex items-center justify-between">
                        <span>4. Inbound Catch Setup</span>
                        <svg class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Right Content Panel -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- SECTION 1: ARCHITECTURE OVERVIEW -->
                    <div x-show="guideSection === 'overview'" class="space-y-6 animate-in fade-in duration-200">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Plugin Engine Architecture</h3>
                            <p class="mt-2 text-slate-600 text-sm leading-relaxed">The pluggable architecture provides double-sided synchronizations using standard domain events for outbound webhooks and automated REST routing for incoming handlers.</p>
                        </div>

                        <!-- Visual Flow Chart -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <!-- Outbound Card -->
                            <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-5 space-y-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">A</span>
                                    <h4 class="font-bold text-slate-800 text-sm">Outbound Event Hooks</h4>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">System events (e.g. Estimate Approved) trigger asynchronously. The listener extracts payloads, processes mapping schemas, signs with HMAC secrets, and sends JSON data to external APIs.</p>
                                <div class="bg-white p-3 rounded-lg border border-indigo-100/50 font-mono text-[10px] text-slate-600 space-y-1 shadow-sm">
                                    <div class="flex justify-between"><span>1. DomainEvent Fired</span><span class="text-indigo-600 font-bold">App</span></div>
                                    <div class="flex justify-between"><span>2. Match Event Hook</span><span class="text-indigo-600">Listener</span></div>
                                    <div class="flex justify-between"><span>3. Build & Sign Payload</span><span class="text-indigo-600">HMAC-SHA256</span></div>
                                    <div class="flex justify-between"><span>4. POST Payload</span><span class="text-emerald-600 font-bold">Slack / CRM</span></div>
                                </div>
                            </div>

                            <!-- Inbound Card -->
                            <div class="bg-purple-50/50 border border-purple-100 rounded-xl p-5 space-y-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs">B</span>
                                    <h4 class="font-bold text-slate-800 text-sm">Inbound Callback Channels</h4>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">External platforms POST webhook data to unique `/plugins/catch/{uuid}` endpoints. The app verifies tokens, applies mapping filters, and executes status transitions or client creation.</p>
                                <div class="bg-white p-3 rounded-lg border border-purple-100/50 font-mono text-[10px] text-slate-600 space-y-1 shadow-sm">
                                    <div class="flex justify-between"><span>1. Webhook Post</span><span class="text-purple-600 font-bold">Stripe / Hub</span></div>
                                    <div class="flex justify-between"><span>2. Match UUID Token</span><span class="text-purple-600">Controller</span></div>
                                    <div class="flex justify-between"><span>3. Parse Mapped Fields</span><span class="text-purple-600">Dot Notation</span></div>
                                    <div class="flex justify-between"><span>4. Run Action & Log</span><span class="text-emerald-600 font-bold">Database</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: CREATING CUSTOM PLUGINS -->
                    <div x-show="guideSection === 'create-plugin'" class="space-y-6 animate-in fade-in duration-200" style="display: none;">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">How to Create Custom Plugins</h3>
                            <p class="mt-2 text-slate-600 text-sm leading-relaxed">Plugins are registered dynamically inside the `plugins` table. You can add them through the UI dashboard or via database seeders using the snippet below.</p>
                        </div>

                        <!-- Code box -->
                        <div class="space-y-2" x-data="{ copyText: 'seeder-code', copied: false }">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Example Database Seeder</span>
                                <button type="button" @click="navigator.clipboard.writeText(document.getElementById('seeder-code').innerText); copied = true; setTimeout(() => copied = false, 2000)" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!copied"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    <span x-show="!copied">Copy Code</span>
                                    <span x-show="copied" class="text-emerald-600" style="display: none;">Copied!</span>
                                </button>
                            </div>
                            <pre id="seeder-code" class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-xs overflow-x-auto select-all leading-relaxed">
DB::table('plugins')->insert([
    'name' => 'Custom ERP Sync',
    'key' => 'custom_erp',
    'description' => 'Synchronizes clients and approves estimates to corporate ERP instances.',
    'version' => '1.0.0',
    'is_active' => false,
    'config_schema' => json_encode([
        [
            'name' => 'api_base_url',
            'label' => 'ERP URL Endpoint',
            'type' => 'text',
            'placeholder' => 'https://erp.mycompany.com/api',
            'required' => true
        ],
        [
            'name' => 'auth_key',
            'label' => 'X-ERP-API-Key',
            'type' => 'password',
            'placeholder' => '••••••••',
            'required' => true
        ]
    ]),
    'config' => json_encode([
        'api_base_url' => '',
        'auth_key' => ''
    ]),
    'created_at' => now(),
    'updated_at' => now(),
]);</pre>
                        </div>

                        <!-- Settings detail -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5 space-y-2">
                            <h4 class="font-bold text-slate-800 text-sm">JSON Configuration Schema Attributes</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">The `config_schema` defines dynamic input forms generated when clicking "Configure API". Supported fields include `text`, `password`, and custom labels.</p>
                        </div>
                    </div>

                    <!-- SECTION 3: OUTBOUND PAYLOAD SPECS -->
                    <div x-show="guideSection === 'outbound-flows'" class="space-y-6 animate-in fade-in duration-200" style="display: none;">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Outbound Webhooks Specs</h3>
                            <p class="mt-2 text-slate-600 text-sm leading-relaxed">When a domain event occurs, active outbound modules POST JSON envelopes. A secure HMAC signature is appended via the header `X-Plugin-Signature` if a signature secret is configured.</p>
                        </div>

                        <!-- Headers specifications -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5 space-y-3">
                            <h4 class="font-bold text-slate-800 text-sm">Dispatched Webhook Headers</h4>
                            <div class="space-y-1 font-mono text-xs">
                                <div class="flex justify-between border-b border-slate-200 py-1">
                                    <span class="font-bold text-slate-700">Content-Type</span>
                                    <span class="text-slate-600">application/json</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-200 py-1">
                                    <span class="font-bold text-slate-700">X-Plugin-Module</span>
                                    <span class="text-slate-600">module_key_name</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="font-bold text-slate-700">X-Plugin-Signature</span>
                                    <span class="text-slate-600">sha256 HMAC of JSON body signed with secret</span>
                                </div>
                            </div>
                        </div>

                        <!-- Example payload -->
                        <div class="space-y-2" x-data="{ copied: false }">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Example Outbound JSON Payload</span>
                                <button type="button" @click="navigator.clipboard.writeText(document.getElementById('outbound-payload-code').innerText); copied = true; setTimeout(() => copied = false, 2000)" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!copied"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    <span x-show="!copied">Copy Payload</span>
                                    <span x-show="copied" class="text-emerald-600" style="display: none;">Copied!</span>
                                </button>
                            </div>
                            <pre id="outbound-payload-code" class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-xs overflow-x-auto select-all leading-relaxed">
{
  "event": "estimate.approved",
  "entity_type": "estimate",
  "entity_id": 45,
  "triggered_by": 2,
  "timestamp": "2026-06-05T08:08:35Z",
  "estimate_number": "EST-2026-0045",
  "total": "15000.00",
  "status": "approved",
  "client": {
    "name": "Acme Corporation",
    "email": "billing@acme.com",
    "phone": "+1 (555) 019-2834"
  }
}</pre>
                        </div>

                        <!-- Placeholder URL replacement -->
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 space-y-2">
                            <h4 class="font-bold text-indigo-900 text-sm">Dynamic URL Placeholders</h4>
                            <p class="text-xs text-indigo-700 leading-relaxed">Target URLs can contain payload keys wrapped in braces. For example, a target URL of `https://crm.myco.com/deals/{estimate_number}` will automatically resolve to `https://crm.myco.com/deals/EST-2026-0045` at runtime.</p>
                        </div>
                    </div>

                    <!-- SECTION 4: INBOUND CATCH SETUP -->
                    <div x-show="guideSection === 'inbound-flows'" class="space-y-6 animate-in fade-in duration-200" style="display: none;">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Inbound Webhooks & Action Mapping</h3>
                            <p class="mt-2 text-slate-600 text-sm leading-relaxed">Catch incoming requests using unique endpoints. You can select actions like updating estimate statuses or creating leads/contacts dynamically.</p>
                        </div>

                        <!-- Tabular mapping configurations -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Stripe Update Estimate Example -->
                            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5 space-y-3">
                                <h4 class="font-bold text-slate-800 text-sm">Example: Stripe Payment Callback</h4>
                                <p class="text-xs text-slate-500 leading-relaxed">To transition estimates to <strong>accepted</strong> when a checkout session succeeds, use these mappings:</p>
                                <div class="bg-white p-3 rounded-lg border border-slate-200 font-mono text-[10px] text-slate-600 space-y-1 shadow-sm">
                                    <div class="font-bold text-indigo-600 border-b pb-1 mb-1">JSON Mapping Input:</div>
                                    <div>"identifier": "data.object.metadata.estimate_id"</div>
                                    <div>"status_field": "type"</div>
                                    <div>"status_map": {</div>
                                    <div class="pl-2">"checkout.session.completed": "accepted"</div>
                                    <div>}</div>
                                </div>
                            </div>

                            <!-- HubSpot Client Sync Example -->
                            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5 space-y-3">
                                <h4 class="font-bold text-slate-800 text-sm">Example: HubSpot Lead Sync</h4>
                                <p class="text-xs text-slate-500 leading-relaxed">To parse new contact webhook payloads from HubSpot and import leads, map fields using dot notation:</p>
                                <div class="bg-white p-3 rounded-lg border border-slate-200 font-mono text-[10px] text-slate-600 space-y-1 shadow-sm">
                                    <div class="font-bold text-indigo-600 border-b pb-1 mb-1">JSON Mapping Input:</div>
                                    <div>"email": "contact.email_address"</div>
                                    <div>"name": "contact.full_name"</div>
                                    <div>"phone": "contact.mobile_phone"</div>
                                    <div>"company": "contact.company_name"</div>
                                </div>
                            </div>
                        </div>

                        <!-- Catcher Token Signature Header -->
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-2">
                            <h4 class="font-bold text-amber-900 text-sm">Security Verification</h4>
                            <p class="text-xs text-amber-700 leading-relaxed">If a "Secret Token" is configured, incoming callback endpoints will require the client to supply it. The system automatically inspects headers `X-Plugin-Token`, `X-Webhook-Signature`, or the query parameter `?token=YOUR_SECRET`.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ── MODAL: CREATE PLUGIN ── -->
    @if ($showCreatePluginModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Create Custom Plugin</h3>
                    <button type="button" wire:click="$set('showCreatePluginModal', false)" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Plugin Name</label>
                        <input type="text" wire:model="pluginName" placeholder="e.g. My Accounting Software" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('pluginName') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Plugin Identifier (Unique Key)</label>
                        <input type="text" wire:model="pluginKey" placeholder="e.g. accounting_soft" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('pluginKey') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Description</label>
                        <textarea wire:model="pluginDescription" placeholder="Explain what this integration does..." rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Version</label>
                        <input type="text" wire:model="pluginVersion" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('pluginVersion') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showCreatePluginModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="button" wire:click="createPlugin" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg">Save Plugin</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── MODAL: CONFIGURE PLUGIN API CREDENTIALS ── -->
    @if ($showConfigModal)
        @php
            $plugin = App\Models\Plugin::find($selectedPluginId);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden transform transition-all">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Configure {{ $plugin?->name }}</h3>
                    <button type="button" wire:click="$set('showConfigModal', false)" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-xs text-slate-500">Provide credentials or endpoint properties needed for the {{ $plugin?->name }} sync channels to authorize properly.</p>
                    
                    @if (empty($plugin?->config_schema))
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500 italic">No global settings needed for this custom connector. Config properties can be set directly in the individual modules below.</div>
                    @else
                        @foreach ($plugin->config_schema as $field)
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">{{ $field['label'] }}</label>
                                @if ($field['type'] === 'password')
                                    <input type="password" wire:model="pluginConfig.{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @else
                                    <input type="text" wire:model="pluginConfig.{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showConfigModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="button" wire:click="saveConfig" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg">Save Config</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── MODAL: CONFIGURE MODULE ── -->
    @if ($showModuleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-xl overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                    <h3 class="text-lg font-bold text-slate-900">{{ $selectedModuleId ? 'Edit Module' : 'Add Connection Module' }}</h3>
                    <button type="button" wire:click="$set('showModuleModal', false)" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Module Name</label>
                            <input type="text" wire:model="moduleName" placeholder="e.g. Post to Channel" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('moduleName') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Unique Key</label>
                            <input type="text" wire:model="moduleKey" placeholder="e.g. slack_approval" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('moduleKey') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Connection Direction</label>
                            <select wire:model.live="moduleType" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="outbound">Outbound (Event -> Target webhook URL)</option>
                                <option value="inbound">Inbound (Generates Catch URL -> Execute Action)</option>
                            </select>
                        </div>
                        <div>
                            @if ($moduleType === 'outbound')
                                <label class="block text-sm font-semibold text-slate-700">System Event Trigger</label>
                                <select wire:model="moduleEventName" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach ($systemEvents as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <label class="block text-sm font-semibold text-slate-700">Action to Execute</label>
                                <select wire:model="moduleActionType" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="update_estimate">Update Estimate status</option>
                                    <option value="create_client">Create/Update Client or Lead</option>
                                    <option value="log_only">Log received event only</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    @if ($moduleType === 'outbound')
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Target URL / API Endpoint</label>
                            <input type="url" wire:model="moduleUrl" placeholder="https://hooks.slack.com/services/..." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">HTTP Method</label>
                                <select wire:model="moduleMethod" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="GET">GET</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-semibold text-slate-700">Webhook Secret / API Key (Optional)</label>
                                <input type="password" wire:model="moduleSecret" placeholder="Signature HMAC secret token" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Required Secret Token Header (Optional)</label>
                            <input type="password" wire:model="moduleSecret" placeholder="Check header X-Plugin-Token / signature check" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    @endif

                     <div>
                        <label class="block text-sm font-semibold text-slate-700">HTTP Request Headers (JSON)</label>
                        <textarea wire:model="moduleHeadersInput" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"></textarea>
                        @error('moduleHeadersInput') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            {{ $moduleType === 'outbound' ? 'Payload Mappings (JSON Key Mappings)' : 'Action Field Mappings (JSON destination -> payload path)' }}
                        </label>
                        <textarea wire:model="moduleMappingsInput" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"></textarea>
                        @error('moduleMappingsInput') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3 flex-shrink-0">
                    <button type="button" wire:click="$set('showModuleModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="button" wire:click="saveModule" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg">Save Module</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── MODAL: LOG INSPECT DETAIL ── -->
    @if ($showLogModal && $selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-3xl overflow-hidden transform transition-all max-h-[85vh] flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Sync Log Details</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Attempted {{ $selectedLog->created_at->format('Y-M-d H:i:s') }} (ID: #{{ $selectedLog->id }})</p>
                    </div>
                    <button type="button" wire:click="$set('showLogModal', false)" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-6 overflow-y-auto flex-1 text-sm text-slate-800">
                    <!-- Top stats summary -->
                    <div class="grid grid-cols-4 gap-4 bg-slate-50 p-4 border border-slate-200/60 rounded-xl">
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase">Module</span>
                            <span class="font-bold">{{ $selectedLog->module->name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase">Direction</span>
                            <span class="font-semibold">{{ ucfirst($selectedLog->direction) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase">Latency</span>
                            <span class="font-semibold text-slate-600">{{ $selectedLog->latency_ms }} ms</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase">HTTP Code</span>
                            <span class="font-bold text-indigo-600">{{ $selectedLog->response_code ?: 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Headers block -->
                    <div class="space-y-1.5">
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Request Headers</span>
                        <pre class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-xs overflow-x-auto select-all">{{ json_encode($selectedLog->headers, JSON_PRETTY_PRINT) }}</pre>
                    </div>

                    <!-- Payload block -->
                    <div class="space-y-1.5">
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Request Payload / Data</span>
                        <pre class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-xs overflow-x-auto select-all">{{ json_encode($selectedLog->payload, JSON_PRETTY_PRINT) }}</pre>
                    </div>

                    <!-- Response block -->
                    <div class="space-y-1.5">
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Response body / Error</span>
                        @if ($selectedLog->status === 'success')
                            <pre class="bg-emerald-950 text-emerald-200 p-4 rounded-xl font-mono text-xs overflow-x-auto select-all">{{ $selectedLog->response_body ?: 'Empty response.' }}</pre>
                        @else
                            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl">
                                <p class="font-semibold text-xs font-mono mb-2">Error Message: {{ $selectedLog->error_message ?? 'N/A' }}</p>
                                <pre class="text-rose-900 text-xs font-mono overflow-x-auto select-all">{{ $selectedLog->response_body ?: 'No response body.' }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end flex-shrink-0">
                    <button type="button" wire:click="$set('showLogModal', false)" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg">Close Inspector</button>
                </div>
            </div>
        </div>
    @endif
</div>
