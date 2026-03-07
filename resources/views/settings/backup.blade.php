<x-app-layout>
    {{-- ── Page Header ──────────────────────────────────────────────────────────── --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <svg class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Backup & Restore
            </h1>
            <p class="mt-1 text-sm text-slate-500">Manage local and Google Drive backups of your database and uploaded files.</p>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            {{-- Run Now Button with Alpine spinner --}}
            <form action="{{ route('backup.run') }}" method="POST" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <button type="submit" :disabled="busy"
                    class="inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60 transition-all">
                    {{-- Spinner shown while busy --}}
                    <svg x-show="busy" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{-- Upload icon shown when idle --}}
                    <svg x-show="!busy" class="-ml-0.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span x-text="busy ? 'Creating Backup…' : 'Create Backup Now'"></span>
                </button>
            </form>

            <a href="{{ route('settings.edit') }}"
                class="inline-flex items-center gap-x-1.5 rounded-lg bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                <svg class="-ml-0.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Settings
            </a>
        </div>
    </div>

    {{-- ── Flash Messages ───────────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 flex items-center gap-3">
            <svg class="h-5 w-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-emerald-700 font-medium">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 flex items-center gap-3">
            <svg class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">

        {{-- ── LEFT: Backup Log Table ───────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Stats strip --}}
            <div class="grid grid-cols-3 gap-4">
                @php
                    $totalCount    = \App\Models\BackupLog::completed()->count();
                    $latestBackup  = \App\Models\BackupLog::completed()->orderBy('created_at','desc')->first();
                    $driveCount    = \App\Models\BackupLog::where('status','drive_uploaded')->count();
                @endphp

                <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Backups</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $totalCount }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Last Backup</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">
                        {{ $latestBackup ? $latestBackup->completed_at?->diffForHumans() : '—' }}
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">On Google Drive</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ $driveCount }}</p>
                </div>
            </div>

            {{-- Backup Logs Table --}}
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Backup History</h2>
                    <span class="text-xs text-slate-400">Keeping latest {{ $keepCount }}</span>
                </div>

                @if($logs->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="mt-3 text-sm font-medium text-slate-500">No backups yet</p>
                        <p class="mt-1 text-xs text-slate-400">Click "Create Backup Now" to get started.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Filename</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Size</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Created</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($logs as $log)
                                    @php $badge = $log->status_badge; @endphp
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-4 py-3 font-mono text-xs text-slate-700 max-w-[200px] truncate">
                                             <div class="flex items-center gap-2">
                                                 <span class="text-sm font-medium text-slate-700 truncate max-w-[200px]" title="{{ $log->filename }}">
                                                     {{ $log->filename }}
                                                 </span>
                                                 @if($log->is_incremental)
                                                     <span class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-100 text-amber-700 rounded uppercase tracking-wider">Incremental</span>
                                                 @endif
                                                 @if(!$log->is_incremental && $log->backup_type === 'final')
                                                     <span class="px-1.5 py-0.5 text-[9px] font-bold bg-indigo-100 text-indigo-700 rounded uppercase tracking-wider">Zero-Downtime</span>
                                                 @endif
                                             </div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">
                                            {{ $log->formatted_size }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @php
                                                $colorMap = [
                                                    'green'  => 'bg-emerald-100 text-emerald-700',
                                                    'blue'   => 'bg-blue-100 text-blue-700',
                                                    'yellow' => 'bg-yellow-100 text-yellow-700',
                                                    'red'    => 'bg-red-100 text-red-700',
                                                    'gray'   => 'bg-slate-100 text-slate-600',
                                                ];
                                                $badgeClass = $colorMap[$badge['color']] ?? 'bg-slate-100 text-slate-600';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                                {{ $badge['label'] }}
                                            </span>
                                            @if($log->google_drive_url)
                                                <a href="{{ $log->google_drive_url }}" target="_blank"
                                                    class="ml-1 inline-flex items-center text-blue-500 hover:text-blue-700"
                                                    title="View on Google Drive">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                                    </svg>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">
                                            <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
                                            <br>
                                            <span class="text-[10px] text-slate-400 capitalize">via {{ $log->triggered_by }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                {{-- Download --}}
                                                <a href="{{ route('backup.download', $log) }}"
                                                    title="Download"
                                                    class="inline-flex items-center justify-center h-7 w-7 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                </a>

                                                {{-- Upload to Drive (if not already on Drive) --}}
                                                @if($log->status !== 'drive_uploaded' && config('services.google_drive.client_id'))
                                                    <form action="{{ route('backup.drive', $log) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" title="Upload to Google Drive"
                                                            class="inline-flex items-center justify-center h-7 w-7 rounded-md text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif

                                                 {{-- Restore --}}
                                                 <form action="{{ route('backup.restore', $log) }}" method="POST" class="inline"
                                                     onsubmit="return confirm('ATOMIC RESTORE: This will overwrite your current database and storage with this backup state. The app will be temporarily unavailable during restoration. Continue?')">
                                                     @csrf
                                                     <button type="submit" title="Restore this backup"
                                                         class="inline-flex items-center justify-center h-7 w-7 rounded-md text-slate-400 hover:text-orange-600 hover:bg-orange-50 transition-colors">
                                                         <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                 d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                         </svg>
                                                     </button>
                                                 </form>

                                                {{-- Delete --}}
                                                <form action="{{ route('backup.destroy', $log) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Delete this backup? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete"
                                                        class="inline-flex items-center justify-center h-7 w-7 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($logs->hasPages())
                        <div class="px-4 py-3 border-t border-slate-100">
                            {{ $logs->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Google Drive Files --}}
            @if(!empty($driveFiles))
                <div class="bg-white shadow-sm ring-1 ring-slate-900/5 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                        <svg class="h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        <h2 class="text-sm font-semibold text-slate-900">Google Drive — Recent Backups</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Size</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Created</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Link</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($driveFiles as $file)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $file['name'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">
                                            @if(isset($file['size']))
                                                {{ round($file['size'] / 1048576, 2) }} MB
                                            @else —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">
                                            {{ isset($file['createdTime']) ? \Carbon\Carbon::parse($file['createdTime'])->diffForHumans() : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if(isset($file['webViewLink']))
                                                <a href="{{ $file['webViewLink'] }}" target="_blank"
                                                    class="text-blue-500 hover:text-blue-700 text-xs font-medium">
                                                    Open ↗
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── RIGHT: Settings Panel ────────────────────────────────────────────── --}}
        <div class="space-y-6">

            {{-- Schedule Settings --}}
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Backup Schedule
                    </h2>
                </div>

                <form action="{{ route('backup.settings') }}" method="POST" class="px-5 py-5 space-y-5">
                    @csrf

                    {{-- Enable / Disable --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900">Auto Backup</p>
                            <p class="text-xs text-slate-500 mt-0.5">Automatically create backups on schedule</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="backup_enabled" value="1" class="sr-only peer"
                                {{ ($settings->get('backup_enabled', '0') === '1') ? 'checked' : '' }}>
                            <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    {{-- Frequency --}}
                    <div>
                        <label for="backup_frequency" class="block text-xs font-medium text-slate-700 mb-1.5">Frequency</label>
                        <select id="backup_frequency" name="backup_frequency"
                            class="block w-full rounded-lg border-0 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-sm">
                            <option value="hourly"  {{ ($settings->get('backup_frequency','daily') === 'hourly')  ? 'selected' : '' }}>Every Hour</option>
                            <option value="daily"   {{ ($settings->get('backup_frequency','daily') === 'daily')   ? 'selected' : '' }}>Daily (2:00 AM)</option>
                            <option value="weekly"  {{ ($settings->get('backup_frequency','daily') === 'weekly')  ? 'selected' : '' }}>Weekly (Mon 2:00 AM)</option>
                            <option value="monthly" {{ ($settings->get('backup_frequency','daily') === 'monthly') ? 'selected' : '' }}>Monthly (1st 2:00 AM)</option>
                        </select>
                    </div>

                    {{-- Keep Count --}}
                    <div>
                        <label for="backup_keep_count" class="block text-xs font-medium text-slate-700 mb-1.5">
                            Keep Last
                            <span class="font-bold text-indigo-600" id="keep-count-label">{{ $settings->get('backup_keep_count', 10) }}</span>
                            Backups
                        </label>
                        <input type="range" name="backup_keep_count" id="backup_keep_count"
                            min="1" max="50"
                            value="{{ $settings->get('backup_keep_count', 10) }}"
                            oninput="document.getElementById('keep-count-label').textContent=this.value"
                            class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>1</span><span>25</span><span>50</span>
                        </div>
                    </div>

                    {{-- Notification Email --}}
                    <div>
                        <label for="backup_notify_email" class="block text-xs font-medium text-slate-700 mb-1.5">
                            Notify Email <span class="text-slate-400 font-normal">(optional)</span>
                        </label>
                        <input type="email" name="backup_notify_email" id="backup_notify_email"
                            value="{{ $settings->get('backup_notify_email', '') }}"
                            placeholder="admin@yourcompany.com"
                            class="block w-full rounded-lg border-0 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-sm">
                        <p class="mt-1 text-[11px] text-slate-400">Receive an email after every backup completes.</p>
                    </div>

                    {{-- Heartbeat URL --}}
                    <div>
                        <label for="backup_heartbeat_url" class="block text-xs font-medium text-slate-700 mb-1.5">
                            Heartbeat URL <span class="text-slate-400 font-normal">(optional)</span>
                        </label>
                        <input type="url" name="backup_heartbeat_url" id="backup_heartbeat_url"
                            value="{{ $settings->get('backup_heartbeat_url', '') }}"
                            placeholder="https://hc-ping.com/your-uuid"
                            class="block w-full rounded-lg border-0 py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-sm">
                        <p class="mt-1 text-[11px] text-slate-400">Pings this URL after a successful <b>consolidation</b> backup.</p>
                    </div>

                    <button type="submit"
                        class="w-full rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 transition-colors">
                        Save Schedule Settings
                    </button>
                </form>
            </div>

            {{-- Google Drive Settings --}}
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 rounded-xl overflow-hidden" x-data="{
                testing: false,
                driveResult: null,
                testDrive() {
                    this.testing = true;
                    this.driveResult = null;
                    fetch('{{ route('backup.test-drive') }}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(d => { this.driveResult = d; this.testing = false; })
                    .catch(e => { this.driveResult = { success: false, message: e.message }; this.testing = false; });
                }
            }">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <svg class="h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-slate-900">Google Drive</h2>
                </div>

                <div class="px-5 py-5">
                    {{-- Enable Drive toggle --}}
                    {{-- NOTE: We pass ALL settings as hidden fields so toggling Drive --}}
                    {{-- does not accidentally reset backup_enabled or other settings. --}}
                    <form action="{{ route('backup.settings') }}" method="POST">
                        @csrf
                        <input type="hidden" name="backup_frequency"
                            value="{{ $settings->get('backup_frequency', 'daily') }}">
                        <input type="hidden" name="backup_keep_count"
                            value="{{ $settings->get('backup_keep_count', 10) }}">
                        <input type="hidden" name="backup_notify_email"
                            value="{{ $settings->get('backup_notify_email', '') }}">
                        {{-- Carry backup_enabled so toggling Drive doesn't zero it out --}}
                        <input type="hidden" name="backup_enabled"
                            value="{{ $settings->get('backup_enabled', '0') }}">

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-slate-900">Enable Drive Upload</p>
                                <p class="text-xs text-slate-500 mt-0.5">Auto-upload backups to Google Drive</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="backup_google_drive_enabled" value="1"
                                    class="sr-only peer"
                                    {{ ($settings->get('backup_google_drive_enabled', '0') === '1') ? 'checked' : '' }}
                                    onchange="this.form.submit()">
                                <div class="w-10 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </form>

                    {{-- Credential status --}}
                    @php
                        $driveConfigured = !empty(config('services.google_drive.client_id'))
                            && !empty(config('services.google_drive.client_secret'))
                            && !empty(config('services.google_drive.refresh_token'));
                    @endphp

                    @if($driveConfigured)
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 mb-4 flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs text-emerald-700 font-medium">Credentials configured in .env</p>
                        </div>
                    @else
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 mb-4">
                            <p class="text-xs text-amber-700 font-medium mb-1">⚠ Credentials not configured</p>
                            <p class="text-[11px] text-amber-600">Add these to your <code class="font-mono bg-amber-100 px-1 rounded">.env</code> file:</p>
                            <pre class="mt-1.5 text-[10px] font-mono text-amber-800 bg-amber-100 rounded p-2 overflow-x-auto">GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REFRESH_TOKEN=
GOOGLE_DRIVE_FOLDER_ID=  # optional</pre>
                        </div>
                    @endif

                    {{-- Test Connection --}}
                    <button type="button" @click="testDrive()" :disabled="testing"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 transition-colors">
                        <svg x-show="!testing" class="h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        <svg x-show="testing" class="animate-spin h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-text="testing ? 'Testing…' : 'Test Drive Connection'"></span>
                    </button>

                    <div x-show="driveResult" x-cloak class="mt-3 rounded-lg px-3 py-2 text-xs font-medium"
                        :class="driveResult?.success ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'"
                        x-text="driveResult?.message">
                    </div>

                    {{-- Setup Guide --}}
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-xs font-semibold text-slate-600 mb-2">How to set up Google Drive</p>
                        <ol class="space-y-1.5 text-[11px] text-slate-500 list-decimal list-inside">
                            <li>Create a project at <a href="https://console.cloud.google.com" target="_blank" class="text-indigo-600 hover:underline">console.cloud.google.com</a></li>
                            <li>Enable the <strong class="text-slate-700">Google Drive API</strong></li>
                            <li>Create OAuth 2.0 credentials (Web app)</li>
                            <li>Get a refresh token via <a href="https://developers.google.com/oauthplayground/" target="_blank" class="text-indigo-600 hover:underline">OAuth Playground</a><br><span class="text-slate-400">Scope: drive.file</span></li>
                            <li>Add credentials to <code class="font-mono bg-slate-100 px-1 rounded">.env</code></li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Storage Info --}}
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 rounded-xl px-5 py-4">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Storage Location</h3>
                <p class="text-xs text-slate-600">
                    Backups are stored in:
                </p>
                <code class="mt-1.5 block text-[11px] font-mono bg-slate-100 text-slate-700 px-3 py-2 rounded-lg break-all">
                    storage/app/private/backups/
                </code>
                <p class="mt-2 text-[11px] text-slate-400">These files are private and not publicly accessible.</p>
            </div>
        </div>
    </div>
</x-app-layout>
