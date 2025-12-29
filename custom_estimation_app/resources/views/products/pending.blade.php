<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pending Product Suggestions</h1>
            <p class="mt-1 text-sm text-gray-500">Review and approve product suggestions from team members</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('products.index') }}"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Products
            </a>
        </div>
    </div>

    @if($products->count() > 0)
        <div class="space-y-4">
            @foreach($products as $product)
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                                <span
                                    class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">
                                    Pending Approval
                                </span>
                            </div>

                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                                <span>Suggested by <strong>{{ $product->suggestedBy->name ?? 'Unknown' }}</strong></span>
                                <span>•</span>
                                <span>{{ $product->created_at->diffForHumans() }}</span>
                                <span>•</span>
                                <span>Category: <strong>{{ $product->category->name ?? 'Uncategorized' }}</strong></span>
                            </div>

                            @if($product->description)
                                <p class="text-sm text-gray-600 mb-3">{{ $product->description }}</p>
                            @endif

                            <div class="flex items-center gap-4">
                                <div>
                                    <span
                                        class="text-2xl font-bold text-indigo-600">₹{{ number_format($product->unit_price, 2) }}</span>
                                    <span class="text-sm text-gray-500">per {{ $product->unit_type }}</span>
                                </div>
                                @if($product->sku)
                                    <div class="text-sm text-gray-500">
                                        SKU: <span class="font-medium">{{ $product->sku }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2 ml-4">
                            <form action="{{ route('products.approve', $product) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Approve
                                </button>
                            </form>

                            <form action="{{ route('products.destroy', $product) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to reject this suggestion?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No pending suggestions</h3>
            <p class="mt-1 text-sm text-gray-500">All product suggestions have been reviewed.</p>
        </div>
    @endif
</x-app-layout>