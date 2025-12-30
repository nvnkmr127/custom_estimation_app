<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Item Packages</h1>
            <p class="mt-2 text-sm text-slate-500">Create bundles of items to quickly add standard sets to any estimate.
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('packages.create') }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all duration-200">
                New Package
            </a>
        </div>
    </div>

    <div class="mt-8">
        <x-card padding="0">
            @if ($packages->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 sm:pl-6">
                                    Package Name</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Items / Price</th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Description</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach ($packages as $package)
                                <tr class="hover:bg-slate-50 transition-colors duration-150">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-semibold text-slate-900 sm:pl-6">
                                        {{ $package->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-bold text-slate-900">{{ config('app.currency_symbol', '$') }}{{ number_format($package->total_price, 2) }}</span>
                                            <span
                                                class="text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ count($package->items ?? []) }}
                                                items</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 font-medium">
                                        {{ Str::limit($package->description, 50) ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end gap-4">
                                            <a href="{{ route('packages.show', $package) }}"
                                                class="text-slate-600 hover:text-slate-900 font-bold transition-colors">View</a>
                                            <a href="{{ route('packages.edit', $package) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-bold transition-colors">Edit</a>
                                            <form action="{{ route('packages.destroy', $package) }}" method="POST"
                                                class="inline-block" onsubmit="return confirm('Delete this package?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-rose-600 hover:text-rose-900 font-bold bg-transparent border-0 p-0 cursor-pointer transition-colors">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($packages->hasPages())
                    <div class="bg-white px-4 py-4 border-t border-slate-200 sm:px-6">
                        {{ $packages->links() }}
                    </div>
                @endif
            @else
                <x-empty-state icon="package" title="No packages found"
                    description="Bundle items together to quickly add standard services to your estimates."
                    actionLabel="New Package" actionUrl="{{ route('packages.create') }}" />
            @endif
        </x-card>
    </div>
</x-app-layout>