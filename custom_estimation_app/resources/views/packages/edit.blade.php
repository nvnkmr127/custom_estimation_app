<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('packages.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                &larr; Back to Packages
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                {{ isset($package) ? 'Edit Package' : 'Create Item Package' }}
            </h1>
        </div>

        <form action="{{ isset($package) ? route('packages.update', $package) : route('packages.store') }}"
            method="POST" class="space-y-8" x-data="{ 
                  items: {{ isset($package) && $package->items ? json_encode($package->items) : '[{item_name: \'\', quantity: 1, unit_type: \'pcs\', unit_price: 0}]' }}
              }">
            @csrf
            @if(isset($package))
                @method('PUT')
            @endif

            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <label for="name" class="block text-sm font-medium leading-6 text-slate-900">Package
                                Name</label>
                            <div class="mt-2">
                                <input type="text" name="name" id="name" value="{{ old('name', $package->name ?? '') }}"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    placeholder="e.g. Electrical Starter Pack" required>
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label for="description"
                                class="block text-sm font-medium leading-6 text-slate-900">Description</label>
                            <div class="mt-2">
                                <textarea name="description" id="description" rows="3"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('description', $package->description ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Editor (Alpine.js) -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h3 class="text-base font-semibold leading-7 text-slate-900 mb-4">Package Items</h3>

                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex gap-4 items-start p-4 bg-slate-50 rounded-lg ring-1 ring-slate-200">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Item Name</label>
                                    <input type="text" :name="`items[${index}][item_name]`" x-model="item.item_name"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="Item Name" required>
                                </div>
                                <div class="w-24">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Qty</label>
                                    <input type="number" step="0.01" :name="`items[${index}][quantity]`"
                                        x-model="item.quantity"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="1" required>
                                </div>
                                <div class="w-24">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Unit</label>
                                    <input type="text" :name="`items[${index}][unit_type]`" x-model="item.unit_type"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="pcs">
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Price</label>
                                    <input type="number" step="0.01" :name="`items[${index}][unit_price]`"
                                        x-model="item.unit_price"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="0.00">
                                </div>
                                <div class="pt-6">
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

                    <button type="button"
                        @click="items.push({item_name: '', quantity: 1, unit_type: 'pcs', unit_price: 0})"
                        class="mt-4 flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Add Item
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('packages.index') }}"
                    class="text-sm font-semibold leading-6 text-slate-900">Cancel</a>
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save
                    Package</button>
            </div>
        </form>
    </div>
</x-app-layout>