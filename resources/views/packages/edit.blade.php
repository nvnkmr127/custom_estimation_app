<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('packages.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                &larr; Back to Packages
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Edit Package</h1>
        </div>

        <form action="{{ route('packages.update', $package) }}" method="POST" class="space-y-8" x-data="{ 
                  isSubmitting: false,
                  items: {{ $package->items ? json_encode($package->items) : '[]' }}
              }">
            @csrf
            @method('PUT')

            <x-card padding="8">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <x-input-label for="name" value="Package Name" required />
                        <x-text-input type="text" name="name" id="name" value="{{ old('name', $package->name) }}"
                            placeholder="e.g. Electrical Starter Pack" required />
                    </div>

                    <div class="col-span-full">
                        <x-input-label for="description" value="Description" />
                        <div class="mt-2">
                            <textarea name="description" id="description" rows="3"
                                class="block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('description', $package->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Items Editor (Alpine.js) -->
            <x-card padding="8">
                <h3 class="text-base font-semibold leading-7 text-slate-900 mb-6">Package Items</h3>

                <div class="space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex gap-4 items-start p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="flex-1">
                                <x-input-label value="Item Name" />
                                <x-text-input type="text" x-bind:name="`items[${index}][item_name]`"
                                    x-model="item.item_name" placeholder="Item Name" required />
                            </div>
                            <div class="w-24">
                                <x-input-label value="Qty" />
                                <x-text-input type="number" step="0.01" x-bind:name="`items[${index}][quantity]`"
                                    x-model="item.quantity" placeholder="1" required />
                            </div>
                            <div class="w-24">
                                <x-input-label value="Unit" />
                                <x-text-input type="text" x-bind:name="`items[${index}][unit_type]`"
                                    x-model="item.unit_type" placeholder="pcs" />
                            </div>
                            <div class="w-32">
                                <x-input-label value="Price" />
                                <x-text-input type="number" step="0.01" x-bind:name="`items[${index}][unit_price]`"
                                    x-model="item.unit_price" placeholder="0.00" />
                            </div>
                            <div class="pt-8">
                                <button type="button" @click="items.splice(index, 1)"
                                    class="text-rose-600 hover:text-rose-900">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="items.push({item_name: '', quantity: 1, unit_type: 'pcs', unit_price: 0})"
                    class="mt-6 flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Add Item
                </button>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('packages.index') }}"
                    class="text-sm font-semibold leading-6 text-slate-600 hover:text-slate-900 transition-colors">Cancel</a>
                <x-primary-button type="submit" class="px-8 py-2.5" x-bind:disabled="isSubmitting"
                    @click="isSubmitting = true" x-bind:class="{ 'opacity-75 cursor-not-allowed': isSubmitting }">
                    <div class="flex items-center">
                        <div x-show="isSubmitting" class="mr-2" style="display: none;">
                            <x-loading-spinner size="5" />
                        </div>
                        <span x-text="isSubmitting ? 'Saving...' : 'Update Package'"></span>
                    </div>
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>