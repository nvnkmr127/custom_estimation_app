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
                                            <select :name="`items[${index}][product_id]`" x-model="item.product_id"
                                                @change="updateItem(item)"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                <option value="">Select a product (Optional)</option>
                                                <template x-for="product in products" :key="product.id">
                                                    <option :value="product.id" x-text="product.name"
                                                        :selected="item.product_id == product.id"></option>
                                                </template>
                                            </select>

                                            <!-- Item Name (Editable if no product, or to override) -->
                                            <label class="block text-xs font-medium text-slate-500 mt-2 mb-1">Item
                                                Name</label>
                                            <input type="text" :name="`items[${index}][item_name]`"
                                                x-model="item.item_name" placeholder="Item Name" required
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">

                                            <!-- Description -->
                                            <label
                                                class="block text-xs font-medium text-slate-500 mt-2 mb-1">Description
                                                (Optional override)</label>
                                            <textarea :name="`items[${index}][description]`" x-model="item.description"
                                                rows="2" placeholder="Enter description to override product default..."
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
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
                    // Normalize items (handle legacy 'name' vs 'item_name')
                    this.items.forEach(item => {
                        if (!item.item_name && item.name) {
                            item.item_name = item.name;
                        }
                    });

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