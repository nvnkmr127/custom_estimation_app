<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $package->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Item Package Details</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <a href="{{ route('packages.edit', $package) }}"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all duration-200">
                Edit Package
            </a>
            <a href="{{ route('packages.index') }}"
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
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Package Information</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Package Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $package->name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Total Price</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">
                                {{ \App\Models\Setting::getCached('currency_symbol', '$') }}{{ number_format($package->total_price, 2) }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $package->description ?? 'No description provided.' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Package Items</h3>
                    <span
                        class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                        {{ count($package->items ?? []) }} items
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
                                    Quantity</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unit Price</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($package->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item['item_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item['quantity'] }} {{ $item['unit_type'] ?? '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \App\Models\Setting::getCached('currency_symbol', '$') }}{{ number_format($item['unit_price'] ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-medium">
                                        {{ \App\Models\Setting::getCached('currency_symbol', '$') }}{{ number_format(($item['quantity'] * ($item['unit_price'] ?? 0)), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No items in this
                                        package.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="3"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total</td>
                                <td class="px-6 py-3 text-left text-sm font-bold text-gray-900">
                                    {{ \App\Models\Setting::getCached('currency_symbol', '$') }}{{ number_format($package->total_price, 2) }}
                                </td>
                            </tr>
                        </tfoot>
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
                    <p class="text-sm text-gray-500 mb-4">Use this package to quickly populate an estimate with these
                        items.</p>

                    <form action="{{ route('packages.destroy', $package) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this package?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-rose-600 shadow-sm ring-1 ring-inset ring-rose-300 hover:bg-rose-50 sm:w-auto">
                            Delete Package
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>