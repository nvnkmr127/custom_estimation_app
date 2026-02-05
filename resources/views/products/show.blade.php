<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs & Actions -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="min-w-0 flex-1">
                <a href="{{ route('products.index') }}"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500 mb-2 block">
                    &larr; Back to Catalog
                </a>
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    {{ $product->name }}
                </h2>
                <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                    @if($product->sku)
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 6.75a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z"
                                    clip-rule="evenodd" />
                            </svg>
                            SKU: {{ $product->sku }}
                        </div>
                    @endif
                    @if($product->category)
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 01-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ $product->category->name }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <a href="{{ route('products.edit', $product) }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Edit Product
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-x-8 gap-y-8 lg:grid-cols-2">
            <!-- Left: Images -->
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 h-fit">
                @if($product->images->count() > 0)
                    <div class="aspect-w-4 aspect-h-3 overflow-hidden rounded-lg bg-gray-100 mb-4 border border-gray-200">
                        <img src="{{ $product->primary_image_url }}"
                            alt="{{ $product->name }}" class="object-cover object-center w-full h-full">
                    </div>
                    @if($product->images->count() > 1)
                        <div class="grid grid-cols-4 gap-4">
                            @foreach($product->images as $image)
                                @php
                                    $url = $image->image_path;
                                    if (!\Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])) {
                                        $url = asset('storage/' . $url);
                                    }
                                @endphp
                                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                    <img src="{{ $url }}"
                                        class="object-cover w-full h-full cursor-pointer hover:opacity-75">
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div
                        class="aspect-[4/3] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200 text-gray-400">
                        <span class="flex flex-col items-center">
                            <svg class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            No Images Available
                        </span>
                    </div>
                @endif
            </div>

            <!-- Right: Details -->
            <div class="space-y-8">

                <!-- Price Card -->
                <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                    <h3 class="text-base font-semibold leading-7 text-gray-900 mb-4">Pricing</h3>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold text-gray-900">${{ number_format($product->unit_price, 2) }}</span>
                        <span class="text-sm font-medium text-gray-500">per {{ $product->unit_type }}</span>
                    </div>
                    @if($product->tax_1 > 0)
                        <div class="mt-2 text-sm text-gray-600">
                            + {{ $product->tax_1 }}% Tax
                        </div>
                    @endif
                </div>

                <!-- Description -->
                @if($product->description)
                    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                        <h3 class="text-base font-semibold leading-7 text-gray-900 mb-4">Description</h3>
                        <div class="prose prose-sm max-w-none text-gray-600">
                            {!! $product->description !!}
                        </div>
                    </div>
                @endif

                <!-- Dimensions -->
                @if(!empty($product->dimensions))
                    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                        <h3 class="text-base font-semibold leading-7 text-gray-900 mb-4">Dimensions</h3>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4">
                            @foreach(['length', 'width', 'height'] as $dim)
                                @if(!empty($product->dimensions[$dim]))
                                    <div class="border-l-4 border-indigo-500 pl-3">
                                        <dt class="text-xs font-medium text-gray-500 uppercase">{{ ucfirst($dim) }}</dt>
                                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $product->dimensions[$dim] }}
                                            {{ $product->dimensions['unit'] ?? '' }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                @endif

                <!-- Tags -->
                @if(!empty($product->tags) && is_array($product->tags))
                    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                        <h3 class="text-base font-semibold leading-7 text-gray-900 mb-4">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bottom: Variants -->
        @if($product->options->count() > 0)
            <div class="mt-8 bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold leading-7 text-gray-900 mb-6">Variants & Options</h3>
                <div class="flow-root">
                    <ul role="list" class="-my-6 divide-y divide-gray-200">
                        @foreach($product->options as $option)
                            <li class="py-6">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ $option->name }}</h4>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($option->values as $value)
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1 text-sm font-medium bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/10">
                                                    {{ $value->value }}
                                                    @if($value->price_adjustment > 0)
                                                        <span
                                                            class="text-xs text-indigo-500 ml-1">(+{{ $product->currency ?? '$' }}{{ $value->price_adjustment }})</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Attributes -->
        @if(!empty($product->attributes))
            <div class="mt-8 bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold leading-7 text-gray-900 mb-6">Additional Specifications</h3>
                <dl class="divide-y divide-gray-100">
                    @foreach($product->attributes as $attr)
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $attr['key'] ?? '' }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $attr['value'] ?? '' }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

    </div>
</x-app-layout>