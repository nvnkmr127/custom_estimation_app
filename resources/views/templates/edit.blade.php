<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('templates.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                &larr; Back to Templates
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                {{ isset($template) ? 'Edit Template' : 'Create Room Template' }}
            </h1>
        </div>

        <form action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}"
            method="POST" class="space-y-8"
            x-data="templateForm({{ $products->toJson() }}, {{ isset($template) && $template->items ? json_encode($template->items) : '[]' }})">
            @csrf
            @if(isset($template))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-x-8 gap-y-8 lg:grid-cols-3">
                <!-- Left Column: Template Details -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl px-4 py-6 sm:p-8">
                        <h2 class="text-base font-semibold leading-7 text-slate-900 mb-4">Template Details</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="name"
                                    class="block text-sm font-medium leading-6 text-slate-900">Name</label>
                                <div class="mt-2">
                                    <input type="text" name="name" id="name" required
                                        value="{{ old('name', $template->name ?? '') }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                            <div>
                                <label for="description"
                                    class="block text-sm font-medium leading-6 text-slate-900">Description</label>
                                <div class="mt-2">
                                    <textarea name="description" id="description" rows="4"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('description', $template->description ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl px-4 py-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-base font-semibold leading-7 text-slate-900">Template Items</h2>
                            <button type="button" @click="addItem()"
                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Add Item
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="relative bg-slate-50 rounded-lg p-4 ring-1 ring-slate-200">
                                    <button type="button" @click="items.splice(index, 1)"
                                        class="absolute top-2 right-2 text-slate-400 hover:text-red-500">
                                        <span class="sr-only">Remove</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-12">
                                        <!-- Product Selection -->
                                        <div class="sm:col-span-6">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">Product</label>
                                            <select :name="`items[${index}][product_id]`" x-model="item.product_id"
                                                @change="updateItem(item)"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                <option value="">Select a product...</option>
                                                <template x-for="product in products" :key="product.id">
                                                    <option :value="product.id" x-text="product.name"
                                                        :selected="item.product_id == product.id"></option>
                                                </template>
                                            </select>
                                            <!-- Hidden Item Name fallback -->
                                            <input type="hidden" :name="`items[${index}][item_name]`"
                                                x-model="item.item_name">
                                        </div>

                                        <!-- Quantity -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">Qty</label>
                                            <input type="number" step="1" :name="`items[${index}][quantity]`"
                                                x-model="item.quantity"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        </div>

                                        <!-- Unit Type (Read Only / Override) -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">Unit</label>
                                            <input type="text" :name="`items[${index}][unit_type]`"
                                                x-model="item.unit_type"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-slate-100">
                                        </div>

                                        <!-- Unit Price (Read Only / Override) -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500 mb-1">Price</label>
                                            <input type="number" step="0.01" :name="`items[${index}][unit_price]`"
                                                x-model="item.unit_price"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-slate-100">
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="items.length === 0"
                                class="text-center py-12 bg-slate-50 rounded-lg ring-1 ring-slate-200 border-dashed border-2 border-slate-300">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-slate-900">No items</h3>
                                <p class="mt-1 text-sm text-slate-500">Get started by adding a product to this template.
                                </p>
                                <div class="mt-6">
                                    <button type="button" @click="addItem()"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                        Add Item
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-x-6 border-t border-gray-900/10 pt-6">
                            <a href="{{ route('templates.index') }}"
                                class="text-sm font-semibold leading-6 text-slate-900">Cancel</a>
                            <button type="submit"
                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Save Template
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('templateForm', (products, initialItems) => ({
                products: products,
                items: initialItems.length ? initialItems : [],

                init() {
                    // Ensure existing items have correct product linking if possible or just display
                    if (this.items.length === 0) {
                        this.addItem();
                    }
                },

                addItem() {
                    this.items.push({
                        item_name: '',
                        product_id: '',
                        quantity: 1,
                        unit_type: 'nos',
                        unit_price: 0
                    });
                },

                updateItem(item) {
                    const product = this.products.find(p => p.id == item.product_id);
                    if (product) {
                        item.item_name = product.name;
                        item.unit_price = product.unit_price || 0;
                        item.unit_type = product.unit_type || 'nos';
                    } else {
                        item.item_name = '';
                        item.unit_price = 0;
                        item.unit_type = 'nos';
                    }
                }
            }));
        });
    </script>
    </div>
</x-app-layout>