<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Approval Checklists</h1>
            <p class="mt-2 text-sm text-slate-500">Manage global checklist items that approvers must verify.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('approval-checklists.create') }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all duration-200">
                Add Checklist Item
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
        <ul role="list" class="divide-y divide-gray-100">
            @forelse($checklists as $item)
                <li class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-gray-50 sm:px-6">
                    <div class="flex min-w-0 gap-x-4">
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-gray-900">
                                {{ $item->task }}
                            </p>
                            @if($item->is_required)
                                <p class="mt-1 flex text-xs leading-5 text-red-500">Required</p>
                            @else
                                <p class="mt-1 flex text-xs leading-5 text-gray-500">Optional</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('approval-checklists.edit', $item) }}"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                Edit
                            </a>
                            <span class="text-gray-300">|</span>
                            <form action="{{ route('approval-checklists.destroy', $item) }}" method="POST"
                                class="inline-block"
                                onsubmit="return confirm('Are you sure you want to delete this item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-sm font-medium text-rose-600 hover:text-rose-500 bg-transparent border-0 cursor-pointer p-0">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500">
                    No checklist items found.
                </li>
            @endforelse
        </ul>
    </div>
</x-app-layout>