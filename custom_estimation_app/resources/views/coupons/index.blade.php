<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Coupon Codes</h1>
            <p class="mt-2 text-sm text-slate-500">Manage promotional discount codes.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('coupons.create') }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create Coupon
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Code
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Usage
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Valid
                        Until</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($coupons as $coupon)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <code
                                class="rounded bg-indigo-50 px-2 py-1 text-sm font-mono font-semibold text-indigo-700">{{ $coupon->code }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $coupon->name }}</div>
                            @if($coupon->description)
                                <div class="text-sm text-gray-500">{{ Str::limit($coupon->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                            @if($coupon->type === 'percentage')
                                {{ $coupon->value }}%
                            @else
                                ₹{{ number_format($coupon->value, 2) }}
                            @endif
                            @if($coupon->max_discount)
                                <div class="text-xs text-gray-500">Max: ₹{{ number_format($coupon->max_discount, 2) }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                            {{ $coupon->usage_count }}
                            @if($coupon->usage_limit)
                                / {{ $coupon->usage_limit }}
                            @else
                                / ∞
                            @endif
                            <div class="text-xs text-gray-500">{{ $coupon->estimates_count }} estimates</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                            @if($coupon->valid_until)
                                {{ $coupon->valid_until->format('M d, Y') }}
                            @else
                                <span class="text-gray-400">No expiry</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($coupon->is_active && $coupon->canBeUsed())
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-700 ring-1 ring-inset ring-green-700/10">
                                    Active
                                </span>
                            @elseif($coupon->is_active)
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 ring-1 ring-inset ring-yellow-700/10">
                                    Limited
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-700/10">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('coupons.show', $coupon) }}"
                                    class="text-gray-600 hover:text-gray-900">View</a>
                                <a href="{{ route('coupons.edit', $coupon) }}"
                                    class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form action="{{ route('coupons.destroy', $coupon) }}" method="POST" class="inline-block"
                                    onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-rose-600 hover:text-rose-900 bg-transparent border-0 cursor-pointer p-0">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">No coupon codes</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a promotional coupon.</p>
                            <div class="mt-6">
                                <a href="{{ route('coupons.create') }}"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                    </svg>
                                    New Coupon
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($coupons->hasPages())
        <div class="mt-6">
            {{ $coupons->links() }}
        </div>
    @endif
</x-app-layout>