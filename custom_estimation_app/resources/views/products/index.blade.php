<x-app-layout>
    <div x-data="{ showUploadModal: false, showRetireModal: false, retireReason: '', activeProductId: null }">
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Product & Service Library</h1>
                <p class="mt-1 text-sm text-gray-500">Manage your product catalog</p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-3">
                <button @click="showUploadModal = true" type="button"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    Bulk Upload
                </button>
                <a href="{{ route('products.create') }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Product
                </a>
                @if(auth()->user()->hasPermission('approve_products') ?? true)
                    <a href="{{ route('products.pending') }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Pending Suggestions
                        @if($pendingCount > 0)
                            <span
                                class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                {{ $pendingCount }}
                            </span>
                        @endif
                    </a>
                @endif
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="mb-6">
            <form method="GET" action="{{ route('products.index') }}" class="flex flex-col sm:flex-row gap-4">
                <!-- Search -->
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="Search products by name, SKU...">
                </div>

                <div class="flex gap-3">
                    <!-- Category Filter -->
                    <div class="w-40">
                        <select name="category" onchange="this.form.submit()"
                            class="block w-full rounded-lg border-slate-300 py-2.5 pl-3 pr-10 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-36">
                        <select name="status" onchange="this.form.submit()"
                            class="block w-full rounded-lg border-slate-300 py-2.5 pl-3 pr-10 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">Status: All</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>Retired
                            </option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                            </option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->anyFilled(['search', 'category', 'status']))
                        <a href="{{ route('products.index') }}"
                            class="inline-flex items-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <!-- Products Table -->
        <x-card padding="0">
            @if($products->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-16">
                                    Image
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                    Product Details
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                    Price
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($products as $product)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="h-12 w-12 flex-shrink-0">
                                            @if($product->images->isNotEmpty())
                                                <img class="h-12 w-12 rounded-lg object-cover border border-slate-200 shadow-sm"
                                                    src="{{ Storage::url($product->images->first()->image_path) }}"
                                                    alt="{{ $product->name }}">
                                            @else
                                                <div
                                                    class="h-12 w-12 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200 shadow-sm">
                                                    <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-semibold text-slate-900">{{ $product->name }}</div>
                                            @if($product->sku)
                                                <div class="text-xs text-slate-500 font-medium">SKU: {{ $product->sku }}</div>
                                            @endif
                                            @if($product->is_featured)
                                                <div class="mt-1">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 uppercase tracking-wide">
                                                        Featured
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                            {{ $product->category->name ?? 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-900 font-bold">
                                            ₹{{ number_format($product->unit_price, 2) }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">per {{ $product->unit_type }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($product->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                                Active
                                            </span>
                                        @elseif($product->status === 'retired')
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-800">
                                                Retired
                                            </span>
                                        @elseif($product->status === 'pending')
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="{{ route('products.edit', $product) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-bold transition-colors">Edit</a>
                                            @if($product->status === 'active')
                                                <button @click="showRetireModal = true; activeProductId = {{ $product->id }}"
                                                    class="text-rose-600 hover:text-rose-900 font-bold transition-colors">
                                                    Retire
                                                </button>
                                            @elseif($product->status === 'retired')
                                                <button onclick="activateProduct({{ $product->id }})"
                                                    class="text-emerald-600 hover:text-emerald-900 font-bold transition-colors">
                                                    Activate
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-4 border-t border-slate-200 sm:px-6">
                    {{ $products->links() }}
                </div>

                <div x-show="showRetireModal" class="relative z-50" style="display: none;">
                    <div class="fixed inset-0 bg-slate-500/75 backdrop-blur-sm transition-opacity"></div>
                    <div class="fixed inset-0 z-50 overflow-y-auto">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                                @click.away="showRetireModal = false">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-2">Retire Product</h3>
                                    <p class="text-sm text-slate-500 mb-4">This product will no longer be available for new
                                        estimates, but will remain in existing ones.</p>
                                    <textarea x-model="retireReason" rows="3" placeholder="Reason for retirement (optional)"
                                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                </div>
                                <div class="mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <button @click="retireProduct(activeProductId, retireReason)" type="button"
                                        class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 transition-colors sm:col-start-2">
                                        Retire Product
                                    </button>
                                    <button @click="showRetireModal = false; retireReason = ''" type="button"
                                        class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors sm:col-start-1 sm:mt-0">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <x-empty-state 
                    icon="product"
                    title="No products found"
                    description="Get started by creating your first product or using bulk upload."
                    actionLabel="Add Product"
                    actionUrl="{{ route('products.create') }}"
                />
            @endif
        </x-card>

        <!-- Upload Modal -->
        <div x-show="showUploadModal" class="relative z-50" style="display: none;">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data"
                        @click.away="showUploadModal = false"
                        class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        @csrf
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Bulk Import Products</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">Upload a CSV file to add multiple products at
                                        once.</p>

                                    <div class="mb-4 text-sm">
                                        <a href="{{ route('products.template') }}"
                                            class="text-indigo-600 hover:text-indigo-500 underline flex items-center justify-center gap-1">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            Download Template CSV
                                        </a>
                                    </div>

                                    <div
                                        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                                        <div class="text-center">
                                            <div class="mt-4 flex text-sm leading-6 text-gray-600 justify-center">
                                                <label for="csv_file"
                                                    class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                                    <span>Upload a file</span>
                                                    <input id="csv_file" name="csv_file" type="file" required
                                                        accept=".csv,.txt" class="sr-only">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs leading-5 text-gray-600">CSV up to 2MB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Import</button>
                            <button type="button" @click="showUploadModal = false"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function retireProduct(productId, reason) {
                fetch(`/products/${productId}/retire`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ retirement_reason: reason })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    });
            }

            function activateProduct(productId) {
                fetch(`/products/${productId}/activate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    });
            }
        </script>
    </div>
</x-app-layout>