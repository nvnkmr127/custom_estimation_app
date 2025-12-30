<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Task</h1>
            <p class="mt-1 text-sm text-gray-500">Update task details for "{{ $task->title }}"</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
            <a href="{{ route('tasks.show', $task) }}"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <form action="{{ route('tasks.update', $task) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                    <input type="text" name="title" id="title" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        value="{{ old('title', $task->title) }}">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $task->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">Priority *</label>
                        <select name="priority" id="priority" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>Low
                            </option>
                            <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>
                                Medium</option>
                            <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>High
                            </option>
                            <option value="urgent" {{ old('priority', $task->priority) == 'urgent' ? 'selected' : '' }}>
                                Urgent</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                        <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>
                                Pending</option>
                            <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>
                                Completed</option>
                            <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>
                                Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Assigned To -->
                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assign To</label>
                        <select name="assigned_to" id="assigned_to"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to', $task->assigned_to) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" name="due_date" id="due_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Estimate -->
                    <div>
                        <label for="estimate_id" class="block text-sm font-medium text-gray-700">Related
                            Estimate</label>
                        <select name="estimate_id" id="estimate_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">None</option>
                            @foreach($estimates as $estimate)
                                <option value="{{ $estimate->id }}" {{ old('estimate_id', $task->estimate_id) == $estimate->id ? 'selected' : '' }}>
                                    {{ $estimate->estimate_number }} - {{ $estimate->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Client -->
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Related Client</label>
                        <select name="client_id" id="client_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">None</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $task->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Update Task
                </button>
            </div>
        </form>
    </div>
</x-app-layout>