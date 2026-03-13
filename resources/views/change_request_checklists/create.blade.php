<x-app-layout>
    <div class="mb-8">
        <a href="{{ route('change-request-checklists.index') }}"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            &larr; Back to Checklists
        </a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Add Change Request Checklist Item</h1>
    </div>

    <div class="max-w-3xl">
        <form action="{{ route('change-request-checklists.store') }}" method="POST"
            class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
            @csrf
            <div class="px-4 py-6 sm:p-8">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-full">
                        <label for="task" class="block text-sm font-medium leading-6 text-gray-900">Task
                            Description</label>
                        <div class="mt-2">
                            <input type="text" name="task" id="task" required
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <p class="mt-1 text-sm text-gray-500">The option displayed to the client (e.g. "I want to change the materials").</p>
                    </div>

                    <div class="sm:col-span-full">
                        <label for="display_order" class="block text-sm font-medium leading-6 text-gray-900">Display Order</label>
                        <div class="mt-2">
                            <input type="number" name="display_order" id="display_order" value="0"
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Lower numbers appear first.</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                <a href="{{ route('change-request-checklists.index') }}"
                    class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save</button>
            </div>
        </form>
    </div>
</x-app-layout>
