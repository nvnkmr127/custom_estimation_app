<x-app-layout>
    @push('scripts')
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
        <style>
            .ql-editor {
                min-height: 150px;
            }
        </style>
    @endpush

    <div class="max-w-4xl mx-auto">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('products.index') }}"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500 mb-2 block">
                    &larr; Back to Products
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Product</h1>
                <p class="mt-1 text-sm text-slate-500">Update product details, pricing, and variants.</p>
            </div>

            <form action="{{ route('products.destroy', $product) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this product?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-900 font-medium">Delete
                    Product</button>
            </form>
        </div>

        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data"
            class="space-y-8" x-data="{ 
                  attributes: {{ json_encode($product->attributes ?? []) }},
                  options: {{ json_encode($product->options->load('values')->map(function ($opt) {
    return [
        'name' => $opt->name,
        'values' => $opt->values->map(function ($v) {
            return ['value' => $v->value, 'price_adjustment' => $v->price_adjustment];
        })
    ];
})) }},
                  tags: '{{ is_array($product->tags) ? implode(',', $product->tags) : ($product->tags ?? '') }}',
                  initQuill() {
                      var quill = new Quill('#editor', { theme: 'snow' });
                      var existing = document.getElementById('description-input').value;
                      if(existing) quill.root.innerHTML = existing;
                      
                      quill.on('text-change', () => {
                          document.getElementById('description-input').value = quill.root.innerHTML;
                      });
                  }
              }" x-init="initQuill()">
            @csrf
            @method('PUT')

            <!-- Core Details -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Product Information</h2>
                    <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                        <div class="sm:col-span-4">
                            <label for="name" class="block text-sm font-medium leading-6 text-slate-900">Product Name
                                *</label>
                            <div class="mt-2">
                                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                                    required
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="sku" class="block text-sm font-medium leading-6 text-slate-900">SKU</label>
                            <div class="mt-2">
                                <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="category_id"
                                class="block text-sm font-medium leading-6 text-slate-900">Category</label>
                            <div class="mt-2">
                                <select name="category_id" id="category_id"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    <option value="">-- No Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="tags" class="block text-sm font-medium leading-6 text-slate-900">Tags</label>
                            <div class="mt-2">
                                <input type="text" name="tags" id="tags" x-model="tags"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    placeholder="classic, premium (comma separated)">
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Description</label>
                            <div class="mt-2 bg-white">
                                <div id="editor" class="rounded-md border-0 ring-1 ring-inset ring-slate-300"></div>
                                <input type="hidden" name="description" id="description-input"
                                    value="{{ old('description', $product->description) }}">
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Product Images</label>

                            <!-- Existing Images -->
                            @if($product->images->count() > 0)
                                <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                    @foreach($product->images as $image)
                                        <div
                                            class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                            <img src="{{ asset('storage/' . $image->image_path) }}" alt=""
                                                class="object-cover w-full h-full">
                                            <!-- We can add delete logic later if needed via array inputs -->
                                            <div
                                                class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <span class="text-white text-xs">Stored</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div
                                class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div class="mt-4 flex text-sm leading-6 text-gray-600 justify-center">
                                        <label for="images"
                                            class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                            <span>Upload new images</span>
                                            <input id="images" name="images[]" type="file" multiple class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs leading-5 text-gray-600">Appends to existing images</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dimensions -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Dimensions</h2>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-4">
                        @php $dims = $product->dimensions ?? []; @endphp
                        <div>
                            <label for="dim_l" class="block text-sm font-medium leading-6 text-slate-900">Length</label>
                            <input type="number" step="0.01" name="dimensions[length]"
                                value="{{ $dims['length'] ?? '' }}" id="dim_l"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <div>
                            <label for="dim_w" class="block text-sm font-medium leading-6 text-slate-900">Width</label>
                            <input type="number" step="0.01" name="dimensions[width]" value="{{ $dims['width'] ?? '' }}"
                                id="dim_w"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <div>
                            <label for="dim_h" class="block text-sm font-medium leading-6 text-slate-900">Height</label>
                            <input type="number" step="0.01" name="dimensions[height]"
                                value="{{ $dims['height'] ?? '' }}" id="dim_h"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <div>
                            <label for="dim_u" class="block text-sm font-medium leading-6 text-slate-900">Unit</label>
                            <select name="dimensions[unit]" id="dim_u"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                @foreach(['in', 'ft', 'cm', 'm', 'mm'] as $u)
                                    <option value="{{ $u }}" {{ ($dims['unit'] ?? '') == $u ? 'selected' : '' }}>{{ $u }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing & Units -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Pricing & Units</h2>
                    <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                        <div class="sm:col-span-2">
                            <label for="unit_price" class="block text-sm font-medium leading-6 text-slate-900">Base Unit
                                Price *</label>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-slate-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" step="0.01" name="unit_price" id="unit_price"
                                    value="{{ old('unit_price', $product->unit_price) }}" required
                                    class="block w-full rounded-md border-0 py-1.5 pl-7 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    placeholder="0.00">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="unit_type" class="block text-sm font-medium leading-6 text-slate-900">Unit
                                Type</label>
                            <div class="mt-2">
                                <select name="unit_type" id="unit_type"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    @foreach(['nos', 'sqft', 'mtr', 'kg', 'set', 'hrs', 'days'] as $type)
                                        <option value="{{ $type }}" {{ old('unit_type', $product->unit_type) == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="calculation_method"
                                class="block text-sm font-medium leading-6 text-slate-900">Calculation Method</label>
                            <div class="mt-2">
                                <select name="calculation_method" id="calculation_method"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    <option value="standard" {{ old('calculation_method', $product->calculation_method) == 'standard' ? 'selected' : '' }}>Standard (Qty
                                        Input)</option>
                                    <option value="formula" {{ old('calculation_method', $product->calculation_method) == 'formula' ? 'selected' : '' }}>Formula (L x W)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="tax_1" class="block text-sm font-medium leading-6 text-slate-900">Tax 1
                                (%)</label>
                            <div class="mt-2">
                                <input type="number" step="0.01" name="tax_1" id="tax_1"
                                    value="{{ old('tax_1', $product->tax_1) }}"
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variants / Options -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold leading-7 text-slate-900">Product Variants & Options</h2>
                        <button type="button"
                            @click="options.push({name: '', values: [{value: '', price_adjustment: 0}]})"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                            + Add Option Group
                        </button>
                    </div>
                    <p class="text-sm text-slate-500 mb-6">Editing these will fully replace the existing options on
                        save.</p>

                    <div class="space-y-6">
                        <template x-for="(opt, optIndex) in options" :key="optIndex">
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 relative">
                                <button type="button" @click="options.splice(optIndex, 1)"
                                    class="absolute top-2 right-2 text-slate-400 hover:text-rose-600">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>

                                <div class="mb-3">
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Option Name
                                        (e.g. Size)</label>
                                    <input type="text" :name="`options[${optIndex}][name]`" x-model="opt.name"
                                        placeholder="e.g. Material"
                                        class="block w-full max-w-sm rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(val, valIndex) in opt.values" :key="valIndex">
                                        <div class="flex gap-2 items-center">
                                            <div class="flex-1">
                                                <input type="text"
                                                    :name="`options[${optIndex}][values][${valIndex}][value]`"
                                                    x-model="val.value" placeholder="Value (e.g. Small)"
                                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            <div class="w-32 relative">
                                                <div
                                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-slate-500 sm:text-xs">+$</span>
                                                </div>
                                                <input type="number" step="0.01"
                                                    :name="`options[${optIndex}][values][${valIndex}][price_adjustment]`"
                                                    x-model="val.price_adjustment" placeholder="0.00"
                                                    class="block w-full rounded-md border-0 py-1.5 pl-7 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            </div>
                                            <button type="button" @click="opt.values.splice(valIndex, 1)"
                                                class="text-slate-400 hover:text-rose-600">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <button type="button" @click="opt.values.push({value: '', price_adjustment: 0})"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-500 flex items-center gap-1 mt-2">
                                        + Add Option Value
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="options.length === 0"
                            class="text-sm text-slate-500 italic py-4 text-center border-2 border-dashed border-slate-200 rounded-lg">
                            Click "Add Option Group" to create variants.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Attributes (Legacy/Extra) -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold leading-7 text-slate-900">Additional Attributes</h2>
                        <button type="button" @click="attributes.push({key:'', value:''})"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                            + Add Field
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(attr, index) in attributes" :key="index">
                            <div class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input type="text" :name="`attributes[${index}][key]`" x-model="attr.key"
                                        placeholder="Key (e.g. Origin)"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <div class="flex-1">
                                    <input type="text" :name="`attributes[${index}][value]`" x-model="attr.value"
                                        placeholder="Value (e.g. Italy)"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <button type="button" @click="attributes.splice(index, 1)"
                                    class="text-slate-400 hover:text-rose-600 p-2">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <div x-show="attributes.length === 0" class="text-sm text-slate-500 italic">
                            Use this for simple key-value pairs (non-variant specs).
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('products.index') }}"
                    class="text-sm font-semibold leading-6 text-slate-900">Cancel</a>
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-8 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</x-app-layout>