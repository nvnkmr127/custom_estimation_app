<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $template->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Room Template Details</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <a href="{{ route('templates.edit', $template) }}"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all duration-200">
                Edit Template
            </a>
            <a href="{{ route('templates.index') }}"
                class="rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all duration-200">
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Basic Info -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Template Information</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Template Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $template->name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <!-- Optional stats or info can go here -->
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $template->description ?? 'No description provided.' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Template Items</h3>
                    <span
                        class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                        {{ count($template->items ?? []) }} items
                    </span>
                </div>
                <div class="border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Item Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product Link</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Default Qty</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Default Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($template->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item['item_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if(isset($item['product_id']) && $item['product_id'])
                                            <span
                                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Linked
                                                Product</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item['quantity'] }} {{ $item['unit_type'] ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ config('app.currency_symbol', '$') }}{{ number_format($item['unit_price'] ?? 0, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No items in this
                                        template.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar / Actions -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Actions</h3>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <p class="text-sm text-gray-500 mb-4">Templates help you start estimates faster by pre-populating
                        rooms.</p>

                    <form action="{{ route('templates.destroy', $template) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this template?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-rose-600 shadow-sm ring-1 ring-inset ring-rose-300 hover:bg-rose-50 sm:w-auto">
                            Delete Template
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>