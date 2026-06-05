<div class="space-y-6">
    <!-- Header Area -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Estimator Calendar</h1>
            <p class="mt-2 text-sm text-slate-600">Track and schedule reminders, estimate expiration dates, and task deadlines in one unified schedule.</p>
        </div>
        <div class="mt-4 sm:ml-4 sm:mt-0 flex items-center space-x-3">
            <button type="button" wire:click="openCreateReminder('{{ now()->format('Y-m-d') }}')" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm transition-all duration-200">
                <svg class="w-5 h-5 mr-1.5 -ml-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Schedule Reminder
            </button>
        </div>
    </div>

    <!-- Alert / Toast -->
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

    <!-- Calendar Wrapper -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border border-slate-200/80 rounded-2xl overflow-hidden flex flex-col">
        <!-- Calendar Navigation Header -->
        <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/50">
            <div class="flex items-center space-x-4">
                <h2 class="text-xl font-bold text-slate-900 leading-6">{{ $monthName }}</h2>
                <div class="inline-flex rounded-lg shadow-sm">
                    <button type="button" wire:click="previousMonth" class="relative inline-flex items-center rounded-l-lg bg-white px-3 py-2 text-slate-400 hover:bg-slate-50 focus:z-10 border border-slate-300">
                        <span class="sr-only">Previous Month</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button type="button" wire:click="$set('year', {{ now()->year }}), $set('month', {{ now()->month }})" class="relative inline-flex items-center bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:z-10 border-y border-slate-300">
                        Today
                    </button>
                    <button type="button" wire:click="nextMonth" class="relative inline-flex items-center rounded-r-lg bg-white px-3 py-2 text-slate-400 hover:bg-slate-50 focus:z-10 border border-slate-300">
                        <span class="sr-only">Next Month</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Legends -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-500">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-amber-500 border border-amber-600/10"></span> Reminders
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-rose-500 border border-rose-600/10"></span> Estimate Expiry
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 border border-emerald-600/10"></span> Tasks
                </span>
            </div>
        </div>

        <!-- Days of the Week headers -->
        <div class="grid grid-cols-7 border-b border-slate-200 text-center text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-50 py-3">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
        </div>

        <!-- Calendar Month Day Grid -->
        <div class="grid grid-cols-7 bg-slate-200 gap-px">
            @foreach ($gridDays as $day)
                @php
                    $isToday = $day['date'] === now()->toDateString();
                    $bgStyle = $day['is_current_month'] 
                        ? ($isToday ? 'bg-indigo-50/50' : 'bg-white') 
                        : 'bg-slate-50/70 text-slate-400';
                @endphp
                <div class="min-h-[130px] p-2 flex flex-col justify-between transition-colors duration-150 {{ $bgStyle }} group relative">
                    <!-- Day Header -->
                    <div class="flex justify-between items-center mb-1">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ $isToday ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-800' }}">
                            {{ $day['day'] }}
                        </span>
                        
                        <!-- Quick Add Link -->
                        <button type="button" wire:click="openCreateReminder('{{ $day['date'] }}')" class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 text-indigo-600 hover:text-indigo-800">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </div>

                    <!-- Events Container -->
                    <div class="flex-1 space-y-1.5 overflow-y-auto max-h-[85px] scrollbar-thin">
                        @foreach ($day['events'] as $evt)
                            <button type="button" wire:click="showEventDetails('{{ $evt['type'] }}', {{ $evt['id'] }})" class="w-full text-left px-2 py-0.5 text-[10px] font-bold rounded border truncate select-none shadow-sm flex items-center justify-between {{ $evt['color'] }}">
                                <span class="truncate">{{ $evt['title'] }}</span>
                                <span class="text-[8px] opacity-65 font-mono ml-1 flex-shrink-0">{{ $evt['time'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ── MODAL: CREATE REMINDER ── -->
    @if ($showReminderModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden transform transition-all">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Schedule Reminder</h3>
                    <button type="button" wire:click="$set('showReminderModal', false)" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4" x-data="{ linkType: 'general' }">
                    <!-- Link selector -->
                    <div class="grid grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Link Context To</label>
                            <select wire:model.live="newReminderLinkType" x-model="linkType" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="general">General (None)</option>
                                <option value="estimate">Estimate</option>
                                <option value="client">Client</option>
                                <option value="task">Task</option>
                            </select>
                        </div>

                        <!-- Estimate option -->
                        <div x-show="linkType === 'estimate'" class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">Select Estimate</label>
                            <select wire:model="newReminderLinkId" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Choose Estimate --</option>
                                @foreach($estimates as $est)
                                    <option value="{{ $est->id }}">Estimate #{{ $est->estimate_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Client option -->
                        <div x-show="linkType === 'client'" class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">Select Client</label>
                            <select wire:model="newReminderLinkId" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Choose Client --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Task option -->
                        <div x-show="linkType === 'task'" class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">Select Task</label>
                            <select wire:model="newReminderLinkId" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Choose Task --</option>
                                @foreach($tasks as $t)
                                    <option value="{{ $t->id }}">{{ $t->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Reminder Title</label>
                        <input type="text" wire:model="newReminderTitle" placeholder="e.g. Call client for signature" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('newReminderTitle') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Description / Details</label>
                        <textarea wire:model="newReminderDescription" placeholder="Add optional details..." rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>

                    <!-- Date & Time -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Remind Date</label>
                            <input type="date" wire:model="newReminderDate" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('newReminderDate') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Remind Time</label>
                            <input type="time" wire:model="newReminderTime" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('newReminderTime') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Delivery channel -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Delivery Channel</label>
                        <select wire:model="newReminderType" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="in_app">In-App Notification</option>
                            <option value="email">Email Notification</option>
                            <option value="both">Both (In-App & Email)</option>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showReminderModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="button" wire:click="saveReminder" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg">Add to Calendar</button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── MODAL: EVENT DETAILS INSPECTOR ── -->
    @if ($showDetailModal && !empty($selectedEventDetails))
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
                @php
                    $theme = match($selectedEventDetails['type']) {
                        'reminder' => ['header' => 'bg-gradient-to-r from-amber-500 to-orange-600 text-white', 'icon' => 'bg-amber-100 text-amber-800'],
                        'estimate' => ['header' => 'bg-gradient-to-r from-rose-500 to-red-600 text-white', 'icon' => 'bg-rose-100 text-rose-800'],
                        'task' => ['header' => 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white', 'icon' => 'bg-emerald-100 text-emerald-800'],
                    };
                @endphp
                <div class="px-6 py-5 flex items-center justify-between {{ $theme['header'] }}">
                    <h3 class="text-lg font-bold leading-5">{{ $selectedEventDetails['title'] }}</h3>
                    <button type="button" wire:click="$set('showDetailModal', false)" class="text-white/85 hover:text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4 text-sm text-slate-800">
                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Scheduled For</span>
                        <span class="font-bold text-slate-900 text-base">{{ $selectedEventDetails['date'] }}</span>
                    </div>

                    @if (isset($selectedEventDetails['delivery']))
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Delivery Mode</span>
                            <span class="font-semibold text-slate-700">{{ $selectedEventDetails['delivery'] }}</span>
                        </div>
                    @endif

                    <div>
                        <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Details / Context</span>
                        <div class="mt-1 bg-slate-50 border border-slate-200/60 rounded-xl p-4 font-normal text-slate-700 leading-relaxed whitespace-pre-line">
                            {{ $selectedEventDetails['description'] }}
                        </div>
                    </div>

                    @if (!empty($selectedEventDetails['link_label']) && !empty($selectedEventDetails['link_url']))
                        <div class="pt-2">
                            <a href="{{ $selectedEventDetails['link_url'] }}" class="inline-flex w-full justify-center items-center px-4 py-2.5 text-sm font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/50 rounded-xl transition-all duration-150">
                                {{ $selectedEventDetails['link_label'] }}
                                <svg class="w-4 h-4 ml-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button" wire:click="$set('showDetailModal', false)" class="px-4 py-2 text-sm font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-lg">Dismiss Inspector</button>
                </div>
            </div>
        </div>
    @endif
</div>
