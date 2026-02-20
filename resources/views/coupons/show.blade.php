<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Coupon: {{ $coupon->code }}</h1>
            <p class="mt-2 text-sm text-slate-500">Coupon Details and Usage</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <a href="{{ route('coupons.edit', $coupon) }}"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all duration-200">
                Edit Coupon
            </a>
            <a href="{{ route('coupons.index') }}"
                class="rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all duration-200">
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Coupon Information</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $coupon->name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Discount Code</dt>
                            <dd class="mt-1 text-sm font-mono font-bold text-indigo-600">{{ $coupon->code }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Discount Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $coupon->type }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Value</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($coupon->type === 'percentage')
                                    {{ $coupon->value }}%
                                @else
                                    {{ \App\Models\Setting::getCurrencySymbol() }}{{ number_format($coupon->value, 2) }}
                                @endif
                            </dd>
                        </div>
                        @if($coupon->max_discount)
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Max Discount</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ \App\Models\Setting::getCurrencySymbol() }}{{ number_format($coupon->max_discount, 2) }}
                                </dd>
                            </div>
                        @endif
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Valid Until</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($coupon->valid_until)
                                    {{ $coupon->valid_until->format('M d, Y') }}
                                @else
                                    <span class="text-gray-500">No Expiration</span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $coupon->description ?? 'No description.' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Recent Usage (If available) -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Usage</h3>
                </div>
                <div class="px-6 py-5">
                    @if($coupon->estimates_count > 0)
                        <ul role="list" class="divide-y divide-gray-100">
                            {{-- Assuming specific estimate relation data might be loaded, otherwise generic message --}}
                            <li class="py-4 text-sm text-gray-500">
                                Used in {{ $coupon->estimates_count }} estimates. (Detailed list implementation pending)
                            </li>
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">This coupon has not been used yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar / Stats -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Usage Stats</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Times Used</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $coupon->usage_count }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Usage Limit</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                @if($coupon->usage_limit)
                                    {{ $coupon->usage_limit }}
                                @else
                                    ∞
                                @endif
                            </dd>
                        </div>
                        <div class="border-t border-gray-100 pt-4 mt-4">
                            <dt class="text-sm font-medium text-gray-500 mb-2">Status</dt>
                            <dd>
                                @if($coupon->is_active && $coupon->canBeUsed())
                                    <span
                                        class="inline-flex w-full justify-center items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                @else
                                    <span
                                        class="inline-flex w-full justify-center items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Inactive
                                        / Expired</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>