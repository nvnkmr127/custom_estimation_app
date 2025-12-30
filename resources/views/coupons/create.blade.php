<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create Coupon Code</h1>
            <p class="mt-2 text-sm text-slate-500">Create a new promotional discount code.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('coupons.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to Coupons
            </a>
        </div>
    </div>

    <form action="{{ route('coupons.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Coupon Details</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="code" class="block text-sm font-medium text-gray-900">Coupon Code *</label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" required
                                placeholder="e.g., SAVE20"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm uppercase @error('code') ring-red-300 @enderror">
                            <p class="mt-1 text-xs text-gray-500">Will be automatically converted to uppercase</p>
                            @error('code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-900">Display Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                placeholder="e.g., 20% Off Summer Sale"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @error('name') ring-red-300 @enderror">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-900">Description</label>
                            <textarea name="description" id="description" rows="2"
                                placeholder="Optional description for internal use"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Discount Configuration -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Discount Configuration</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-900">Discount Type *</label>
                            <select name="type" id="type" required
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>Percentage
                                    (%)</option>
                                <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="value" class="block text-sm font-medium text-gray-900">Discount Value *</label>
                            <input type="number" name="value" id="value" value="{{ old('value') }}" required step="0.01"
                                min="0" placeholder="e.g., 20 or 500"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm @error('value') ring-red-300 @enderror">
                            @error('value')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="min_amount" class="block text-sm font-medium text-gray-900">Minimum Purchase
                                Amount</label>
                            <input type="number" name="min_amount" id="min_amount" value="{{ old('min_amount') }}"
                                step="0.01" min="0" placeholder="Optional"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Minimum estimate total required</p>
                        </div>

                        <div>
                            <label for="max_discount" class="block text-sm font-medium text-gray-900">Maximum Discount
                                Cap</label>
                            <input type="number" name="max_discount" id="max_discount" value="{{ old('max_discount') }}"
                                step="0.01" min="0" placeholder="Optional"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Maximum discount amount (for % coupons)</p>
                        </div>
                    </div>
                </div>

                <!-- Usage & Validity -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Usage & Validity</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="usage_limit" class="block text-sm font-medium text-gray-900">Usage Limit</label>
                            <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit') }}"
                                min="1" placeholder="Unlimited"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Total number of times coupon can be used</p>
                        </div>

                        <div>
                            <label for="valid_from" class="block text-sm font-medium text-gray-900">Valid From</label>
                            <input type="date" name="valid_from" id="valid_from" value="{{ old('valid_from') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-gray-900">Valid Until</label>
                            <input type="date" name="valid_until" id="valid_until" value="{{ old('valid_until') }}"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div class="flex items-center pt-8">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-x-6">
                    <a href="{{ route('coupons.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Coupon
                    </button>
                </div>
            </div>

            <!-- Preview/Help -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">💡 Tips</h3>
                    <div class="space-y-4 text-sm text-gray-600">
                        <div>
                            <p class="font-medium text-gray-900">Coupon Code</p>
                            <p class="mt-1">Use memorable codes like SAVE20, WELCOME10, or SUMMER2024</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Discount Type</p>
                            <p class="mt-1"><strong>Percentage:</strong> 20% off<br><strong>Fixed:</strong> ₹500 off</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Min Amount</p>
                            <p class="mt-1">Set minimum purchase to encourage larger orders</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Max Discount</p>
                            <p class="mt-1">Cap percentage discounts to control costs</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Usage Limit</p>
                            <p class="mt-1">Leave empty for unlimited uses</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>