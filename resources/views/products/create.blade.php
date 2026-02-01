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
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Product</h1>
                <p class="mt-1 text-sm text-slate-500">Add a new item to your catalog with detailed specs.</p>
            </div>
        </div>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8"
            @submit="isSubmitting = true" x-data="{ 
                  isSubmitting: false,
                  attributes: {{ json_encode(old('attributes', [])) }},
                  options: {{ json_encode(old('options', [])) }},
                  tags: '',
                  isGenerating: false,
                  quillInstance: null,
                  initQuill() {
                      this.quillInstance = new Quill('#editor', {
                        theme: 'snow'
                      });
                      this.quillInstance.on('text-change', () => {
                          document.getElementById('description-input').value = this.quillInstance.root.innerHTML;
                      });
                      
                      // Restore description if old exists
                      var oldDesc = document.getElementById('description-input').value;
                      if(oldDesc) {
                        this.quillInstance.root.innerHTML = oldDesc;
                      }
                  },
                  async generateDescription() {
                      const name = document.getElementById('name').value;
                      if (!name) {
                          alert('Please enter a Product Name first.');
                          return;
                      }

                      this.isGenerating = true;

                      try {
                          const response = await fetch('{{ route('ai.generate') }}', {
                              method: 'POST',
                              headers: {
                                  'Content-Type': 'application/json',
                                  'X-CSRF-TOKEN': '{{ csrf_token() }}'
                              },
                              body: JSON.stringify({
                                  name: name,
                                  attributes: this.attributes.reduce((obj, item) => ({...obj, [item.key]: item.value}), {})
                              })
                          });

                          if (!response.ok) throw new Error('Generation failed');

                          const data = await response.json();
                          if (this.quillInstance) {
                              this.quillInstance.root.innerHTML = data.description;
                              // Trigger text-change event manually if needed, but innerHTML set usually doesn't trigger it same way
                              document.getElementById('description-input').value = data.description;
                          }
                      } catch (error) {
                          console.error(error);
                          alert('Failed to generate description. Please check your API key or try again.');
                      } finally {
                          this.isGenerating = false;
                      }
                  }
              }" x-init="initQuill()">
            @csrf

            <!-- Core Details -->
            <x-card padding="8">
                <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Product Information</h2>
                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                    <div class="col-span-full">
                        <x-input-label for="name" value="Product Name" required />
                        <x-text-input type="text" name="name" id="name" value="{{ old('name') }}" required
                            placeholder="e.g. Marble Flooring Tile" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input type="text" name="sku" id="sku" value="{{ old('sku') }}" placeholder="OPT-001" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="category_id" value="Category" />
                        <select name="category_id" id="category_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">-- No Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="tags" value="Tags" />
                        <x-text-input type="text" name="tags" id="tags" value="{{ old('tags') }}"
                            placeholder="classic, premium" />
                    </div>

                    <div class="col-span-full">
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label value="Description" />
                            <button type="button" @click="generateDescription" :disabled="isGenerating"
                                class="text-xs flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <svg x-show="!isGenerating" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <svg x-show="isGenerating" class="animate-spin h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="isGenerating ? 'Generating...' : 'Auto-Generate with AI'"></span>
                            </button>
                        </div>
                        <div class="mt-2 bg-white">
                            <div id="editor"
                                class="rounded-lg border-0 ring-1 ring-inset ring-slate-300 overflow-hidden"></div>
                            <input type="hidden" name="description" id="description-input"
                                value="{{ old('description') }}">
                        </div>
                    </div>

                    <div class="col-span-full">
                        <x-input-label value="Product Images" />
                        <div
                            class="mt-2 flex justify-center rounded-xl border border-dashed border-slate-300 px-6 py-10 transition-colors hover:border-slate-400">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="currentColor"
                                    aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="mt-4 flex text-sm leading-6 text-slate-600 justify-center">
                                    <label for="images"
                                        class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                        <span>Upload images</span>
                                        <input id="images" name="images[]" type="file" multiple class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs leading-5 text-slate-500">PNG, JPG, WEBP up to 5MB</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Dimensions -->
            <x-card padding="8">
                <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Dimensions</h2>
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-1">
                        <x-input-label for="dim_l" value="Length" />
                        <x-text-input type="number" step="0.01" name="dimensions[length]" id="dim_l"
                            value="{{ old('dimensions.length') }}" />
                    </div>
                    <div class="sm:col-span-1">
                        <x-input-label for="dim_w" value="Width" />
                        <x-text-input type="number" step="0.01" name="dimensions[width]" id="dim_w"
                            value="{{ old('dimensions.width') }}" />
                    </div>
                    <div class="sm:col-span-1">
                        <x-input-label for="dim_h" value="Height" />
                        <x-text-input type="number" step="0.01" name="dimensions[height]" id="dim_h"
                            value="{{ old('dimensions.height') }}" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="dim_u" value="Unit" />
                        <select name="dimensions[unit]" id="dim_u"
                            class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="in" {{ old('dimensions.unit') == 'in' ? 'selected' : '' }}>Inches</option>
                            <option value="ft" {{ old('dimensions.unit') == 'ft' ? 'selected' : '' }}>Feet</option>
                            <option value="cm" {{ old('dimensions.unit') == 'cm' ? 'selected' : '' }}>cm</option>
                            <option value="m" {{ old('dimensions.unit') == 'm' ? 'selected' : '' }}>Meters</option>
                            <option value="mm" {{ old('dimensions.unit') == 'mm' ? 'selected' : '' }}>mm</option>
                        </select>
                    </div>
                </div>
            </x-card>

            <!-- Pricing & Units -->
            <x-card padding="8">
                <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Pricing & Units</h2>
                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                    <div class="sm:col-span-2">
                        <x-input-label for="unit_price" value="Base Unit Price" required />
                        <div class="mt-2 relative rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-slate-500 sm:text-sm">{{ config('app.currency_symbol', '$') }}</span>
                            </div>
                            <input type="number" step="0.01" name="unit_price" id="unit_price"
                                value="{{ old('unit_price') }}" required
                                class="block w-full rounded-lg border-slate-300 py-1.5 pl-7 text-slate-900 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                placeholder="0.00">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="calculation_method" value="Calculation Method" />
                        <select name="calculation_method" id="calculation_method"
                            class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="standard" {{ old('calculation_method') == 'standard' ? 'selected' : '' }}>
                                Standard (Manual Qty)</option>
                            <option value="area" {{ old('calculation_method') == 'area' ? 'selected' : '' }}>Area (L x W)
                            </option>
                            <option value="volume" {{ old('calculation_method') == 'volume' ? 'selected' : '' }}>Volume (L
                                x W x H)</option>
                            <option value="formula" {{ old('calculation_method') == 'formula' ? 'selected' : '' }}>Custom
                                Formula</option>
                        </select>
                    </div>

                    <div class="sm:col-span-4"
                        x-show="document.getElementById('calculation_method').value === 'formula'" x-cloak>
                        <x-input-label for="formula" value="Custom Formula Pattern" />
                        <x-text-input type="text" name="formula" id="formula" value="{{ old('formula') }}"
                            placeholder="e.g. (L + W) * 2 * H" class="mt-2" />
                        <p class="mt-1 text-[10px] text-slate-500 italic">Use variables: L (Length), W (Width), H
                            (Height)</p>
                    </div>

                    <div class="sm:col-span-3">
                        <x-input-label for="unit_type_id" value="Unit Classification" />
                        <select name="unit_type_id" id="unit_type_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">-- No Classification --</option>
                            @foreach($unitTypes as $ut)
                                <option value="{{ $ut->id }}" {{ old('unit_type_id') == $ut->id ? 'selected' : '' }}>
                                    {{ $ut->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-3">
                        <x-input-label for="tax_1" value="Tax 1 (%)" />
                        <x-text-input type="number" step="0.01" name="tax_1" id="tax_1" value="{{ old('tax_1', 0) }}" />
                    </div>
                </div>
            </x-card>

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
                    <p class="text-sm text-slate-500 mb-6">Define variations like Color or Size, and any price
                        adjustment for that specific option.</p>

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
                    class="text-sm font-semibold leading-6 text-slate-600 hover:text-slate-900 transition-colors">Cancel</a>
                <x-primary-button type="submit" class="px-8 py-2.5" x-bind:disabled="isSubmitting"
                    x-bind:class="{ 'opacity-75 cursor-not-allowed': isSubmitting }">
                    <div class="flex items-center">
                        <div x-show="isSubmitting" class="mr-2" style="display: none;">
                            <x-loading-spinner size="5" />
                        </div>
                        <span x-text="isSubmitting ? 'Creating...' : 'Create Product'"></span>
                    </div>
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>