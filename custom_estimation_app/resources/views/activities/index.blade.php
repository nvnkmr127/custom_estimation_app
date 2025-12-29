<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
            <p class="mt-1 text-sm text-gray-500">Track all system actions and changes</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <!-- Optional Export Button -->
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('activities.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                    <select name="action"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Actions</option>
                        <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                        <option value="approved" {{ request('action') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="accepted" {{ request('action') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject Type</label>
                    <select name="subject_type"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Types</option>
                        <option value="App\Models\Estimate" {{ request('subject_type') == 'App\Models\Estimate' ? 'selected' : '' }}>Estimate</option>
                        <option value="App\Models\Task" {{ request('subject_type') == 'App\Models\Task' ? 'selected' : '' }}>Task</option>
                        <option value="App\Models\Client" {{ request('subject_type') == 'App\Models\Client' ? 'selected' : '' }}>Client</option>
                        <option value="App\Models\Product" {{ request('subject_type') == 'App\Models\Product' ? 'selected' : '' }}>Product</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Filter
                    </button>
                    <a href="{{ route('activities.index') }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Activity Log List -->
    <div class="bg-white shadow-sm overflow-hidden sm:rounded-lg">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($activities as $activity)
                <li class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-indigo-600">
                                    {{ $activity->user ? $activity->user->name : 'System' }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $activity->action }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-900 mt-1">
                                {{ $activity->description }}
                            </p>
                            @if($activity->properties)
                                <div class="mt-2 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100 italic">
                                    {{-- Optional: Show detailed changes here --}}
                                    Changed: {{ implode(', ', array_keys($activity->properties)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col items-end text-right">
                            <time datetime="{{ $activity->created_at }}" class="text-xs text-gray-500">
                                {{ $activity->created_at->format('M d, Y H:i') }}
                            </time>
                            <span class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">
                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                            </span>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <p class="text-sm text-gray-500 italic">No activity logs found matching your criteria.</p>
                </li>
            @endforelse
        </ul>

        @if($activities->hasPages())
            <div class="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</x-app-layout>