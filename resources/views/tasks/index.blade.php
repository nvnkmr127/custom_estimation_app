<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
            <p class="mt-1 text-sm text-gray-500">Manage and track your tasks</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('tasks.create') }}"
                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Task
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('tasks.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress
                        </option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Priorities</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                    <select name="assigned_to"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Users</option>
                        <option value="me" {{ request('assigned_to') == 'me' ? 'selected' : '' }}>My Tasks</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Filter
                    </button>
                    <a href="{{ route('tasks.index') }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tasks List -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="divide-y divide-gray-200">
            @forelse($tasks as $task)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <a href="{{ route('tasks.show', $task) }}" class="hover:text-indigo-600">
                                        {{ $task->title }}
                                    </a>
                                </h3>

                                @if($task->priority === 'urgent')
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Urgent</span>
                                @elseif($task->priority === 'high')
                                    <span
                                        class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">High</span>
                                @elseif($task->priority === 'medium')
                                    <span
                                        class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Medium</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Low</span>
                                @endif

                                @if($task->status === 'completed')
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Completed</span>
                                @elseif($task->status === 'in_progress')
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">In
                                        Progress</span>
                                @elseif($task->isOverdue())
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Overdue</span>
                                @endif
                            </div>

                            @if($task->description)
                                <p class="text-sm text-gray-600 mb-3">{{ Str::limit($task->description, 150) }}</p>
                            @endif

                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                @if($task->assignedTo)
                                    <span>Assigned to: <strong>{{ $task->assignedTo->name }}</strong></span>
                                @endif
                                @if($task->due_date)
                                    <span>Due: <strong
                                            class="{{ $task->isOverdue() ? 'text-red-600' : '' }}">{{ $task->due_date->format('M d, Y') }}</strong></span>
                                @endif
                                @if($task->estimate)
                                    <span>Estimate: <strong>{{ $task->estimate->estimate_number }}</strong></span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ml-4">
                            @if($task->status !== 'completed')
                                <form action="{{ route('tasks.complete', $task) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center rounded-md bg-green-50 px-3 py-2 text-sm font-semibold text-green-700 hover:bg-green-100">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Complete
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('tasks.edit', $task) }}"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Edit
                            </a>

                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center rounded-md bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No tasks found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new task.</p>
                    <div class="mt-6">
                        <a href="{{ route('tasks.create') }}"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Task
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        @if($tasks->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
