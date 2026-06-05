<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reminders</h1>
            <p class="mt-1 text-sm text-gray-500">Your scheduled reminders and notifications</p>
        </div>
    </div>

    @php($defaultRemindAt = now()->addHour()->format('Y-m-d\TH:i'))
    
    @if($linkedEntityInfo)
        <form method="POST" action="{{ route('reminders.store') }}" class="mb-6 bg-white shadow-sm overflow-hidden sm:rounded-lg p-6 space-y-4">
            @csrf
            <input type="hidden" name="remindable_type" value="{{ $remindableType }}">
            <input type="hidden" name="remindable_id" value="{{ $remindableId }}">

            <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4 text-xs text-indigo-800 flex items-center justify-between">
                <span class="flex items-center gap-2 font-semibold">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    Linked Entity Context: <strong class="text-indigo-950">{{ $linkedEntityInfo }}</strong>
                </span>
                <a href="{{ route('reminders.index') }}" class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline">Clear Context</a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input name="title" required value="{{ old('title') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Remind At</label>
                    <input type="datetime-local" name="remind_at" required value="{{ old('remind_at', $defaultRemindAt) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Delivery</label>
                    <select name="type" required
                        class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="in_app" @selected(old('type') === 'in_app')>In-App</option>
                        <option value="email" @selected(old('type') === 'email')>Email</option>
                        <option value="both" @selected(old('type') === 'both')>Both</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Add Reminder
                </button>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('reminders.store') }}" class="mb-6 bg-white shadow-sm overflow-hidden sm:rounded-lg p-6 space-y-4" x-data="{ contextType: 'general' }">
            @csrf
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Link Reminder To</label>
                    <select x-model="contextType" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="general">General (None)</option>
                        <option value="estimate">Estimate</option>
                        <option value="client">Client</option>
                        <option value="task">Task</option>
                    </select>
                </div>

                <!-- User Hidden Context -->
                <div x-show="contextType === 'general'">
                    <input type="hidden" name="remindable_type" value="{{ \App\Models\User::class }}">
                    <input type="hidden" name="remindable_id" value="{{ Auth::id() }}">
                </div>

                <!-- Estimate Selector -->
                <div x-show="contextType === 'estimate'" class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Select Estimate</label>
                    <input type="hidden" name="remindable_type" value="{{ \App\Models\Estimate::class }}">
                    <select name="remindable_id" :required="contextType === 'estimate'" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Choose Estimate --</option>
                        @foreach($estimates as $est)
                            <option value="{{ $est->id }}">Estimate #{{ $est->estimate_number }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Client Selector -->
                <div x-show="contextType === 'client'" class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Select Client</label>
                    <input type="hidden" name="remindable_type" value="{{ \App\Models\Client::class }}">
                    <select name="remindable_id" :required="contextType === 'client'" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Choose Client --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Task Selector -->
                <div x-show="contextType === 'task'" class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Select Task</label>
                    <input type="hidden" name="remindable_type" value="{{ \App\Models\Task::class }}">
                    <select name="remindable_id" :required="contextType === 'task'" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Choose Task --</option>
                        @foreach($tasks as $t)
                            <option value="{{ $t->id }}">{{ $t->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input name="title" required value="{{ old('title') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Remind At</label>
                    <input type="datetime-local" name="remind_at" required value="{{ old('remind_at', $defaultRemindAt) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Delivery</label>
                    <select name="type" required
                        class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="in_app" @selected(old('type') === 'in_app')>In-App</option>
                        <option value="email" @selected(old('type') === 'email')>Email</option>
                        <option value="both" @selected(old('type') === 'both')>Both</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Add Reminder
                </button>
            </div>
        </form>
    @endif

    <div class="bg-white shadow-sm overflow-hidden sm:rounded-lg">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($reminders as $reminder)
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ $reminder->title }}</span>
                                @if($reminder->is_sent)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Sent</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Pending</span>
                                @endif
                            </div>
                            @if($reminder->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $reminder->description }}</p>
                            @endif
                            <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $reminder->remind_at->format('M d, Y H:i') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    @if ($reminder->remindable_type === \App\Models\Estimate::class && $reminder->remindable)
                                        <a href="{{ route('estimates.show', $reminder->remindable_id) }}" class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline">Estimate #{{ $reminder->remindable->estimate_number }}</a>
                                    @elseif ($reminder->remindable_type === \App\Models\Client::class && $reminder->remindable)
                                        <a href="{{ route('clients.show', $reminder->remindable_id) }}" class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline">Client: {{ $reminder->remindable->name }}</a>
                                    @elseif ($reminder->remindable_type === \App\Models\Task::class && $reminder->remindable)
                                        <a href="{{ route('tasks.show', $reminder->remindable_id) }}" class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline">Task: {{ $reminder->remindable->title }}</a>
                                    @else
                                        {{ class_basename($reminder->remindable_type) }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex items-center gap-2">
                            @if(!$reminder->is_sent)
                                <form action="{{ route('reminders.read', $reminder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Dismiss</button>
                                </form>
                            @endif
                            <form action="{{ route('reminders.destroy', $reminder) }}" method="POST"
                                onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No reminders</h3>
                    <p class="mt-1 text-sm text-gray-500">You don't have any scheduled reminders.</p>
                </li>
            @endforelse
        </ul>

        @if($reminders->hasPages())
            <div class="px-4 py-4 sm:px-6 border-t border-gray-200">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
