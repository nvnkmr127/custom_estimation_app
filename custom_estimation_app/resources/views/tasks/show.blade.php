<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $task->title }}</h1>
                @if($task->priority === 'urgent')
                    <span
                        class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 uppercase tracking-wider">Urgent</span>
                @elseif($task->priority === 'high')
                    <span
                        class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 uppercase tracking-wider">High</span>
                @elseif($task->priority === 'medium')
                    <span
                        class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 uppercase tracking-wider">Medium</span>
                @else
                    <span
                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 uppercase tracking-wider">Low</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">Task details and activity</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
            @if($task->status !== 'completed')
                <form action="{{ route('tasks.complete', $task) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Mark as Complete
                    </button>
                </form>
            @endif
            <a href="{{ route('tasks.edit', $task) }}"
                class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Edit Task
            </a>
            <a href="{{ route('tasks.index') }}"
                class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                <h2 class="text-base font-semibold leading-7 text-gray-900 mb-4">Description</h2>
                <div class="prose prose-sm max-w-none text-gray-600">
                    {!! nl2br(e($task->description)) !!}
                    @if(!$task->description)
                        <span class="italic text-gray-400">No description provided.</span>
                    @endif
                </div>
            </div>

            <!-- Activity / Comments -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-6">
                    <h3 class="text-sm font-semibold leading-6 text-gray-900">Task Activity</h3>
                </div>
                <div class="p-6">
                    <ul role="list" class="space-y-6">
                        @forelse($task->activities()->latest()->take(10)->get() as $activity)
                            <li class="relative flex gap-x-4">
                                <div class="absolute left-0 top-0 flex w-6 justify-center -bottom-6">
                                    <div class="w-px bg-gray-200"></div>
                                </div>
                                <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                                    <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                                </div>
                                <div class="flex-auto py-0.5 text-xs leading-5 text-gray-500">
                                    <span
                                        class="font-medium text-gray-900">{{ $activity->user ? $activity->user->name : 'System' }}</span>
                                    {{ $activity->description }}
                                    <time datetime="{{ $activity->created_at }}"
                                        class="flex-none text-gray-400 ml-1">{{ $activity->created_at->diffForHumans() }}</time>
                                </div>
                            </li>
                        @empty
                            <li class="text-xs text-gray-400 italic">No activity recorded yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                <h2 class="text-base font-semibold leading-7 text-gray-900 mb-6">Task Info</h2>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</dt>
                        <dd class="mt-1">
                            @if($task->status === 'completed')
                                <span
                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Completed</span>
                            @elseif($task->status === 'in_progress')
                                <span
                                    class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">In
                                    Progress</span>
                            @elseif($task->status === 'cancelled')
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Cancelled</span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pending</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($task->assignedTo)
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-6 w-6 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-700">
                                        {{ substr($task->assignedTo->name, 0, 1) }}
                                    </span>
                                    {{ $task->assignedTo->name }}
                                </div>
                            @else
                                <span class="text-gray-400 italic">Unassigned</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $task->createdBy->name ?? 'System' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</dt>
                        <dd class="mt-1 text-sm {{ $task->isOverdue() ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                            {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}
                            @if($task->isOverdue())
                                <span class="block text-[10px] text-red-500 uppercase font-bold mt-0.5">Overdue</span>
                            @endif
                        </dd>
                    </div>

                    @if($task->completed_at)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $task->completed_at->format('M d, Y H:i') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if($task->estimate_id || $task->client_id)
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 mb-6">Related Information</h2>

                    <dl class="space-y-4">
                        @if($task->estimate)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Estimate</dt>
                                <dd class="mt-1 text-sm">
                                    <a href="{{ route('estimates.show', $task->estimate) }}"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        #{{ $task->estimate->estimate_number }} - {{ $task->estimate->title }}
                                    </a>
                                </dd>
                            </div>
                        @endif

                        @if($task->client)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Client</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $task->client->name }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>