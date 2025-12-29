<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Product Categories</h1>
            <p class="mt-2 text-sm text-slate-500">Manage categories to organize your products.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('categories.create') }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all duration-200">
                Add Category
            </a>
        </div>
    </div>

    <div class="mt-8 flow-root">
        <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm ring-1 ring-black ring-opacity-5 sm:rounded-xl bg-white">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:pl-6">
                                    Name</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Products Count</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Parent Category</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span
                                        class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse ($categories as $category)
                                <tr class="hover:bg-slate-50/50 transition-colors duration-150">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        {{ $category->name }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ $category->products_count }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ $category->parent->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('categories.edit', $category) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</a>
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                                class="inline-block" onsubmit="return confirm('Delete this category?');">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-rose-600 hover:text-rose-900 font-medium bg-transparent border-0 cursor-pointer p-0">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-10 text-center text-sm text-slate-500">No categories
                                        found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>