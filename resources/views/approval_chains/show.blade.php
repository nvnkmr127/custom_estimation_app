<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back Button & Header -->
        <div class="mb-8">
            <a href="{{ route('approval-chains.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-6">
                <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Chains
            </a>

            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $approvalChain->name }}</h1>
                    <div class="mt-2 flex items-center gap-3">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $approvalChain->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $approvalChain->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="text-gray-400">|</span>
                        <span class="text-sm text-gray-500">{{ $approvalChain->currency }} Currency</span>
                    </div>
                </div>
                <div class="mt-6 sm:mt-0 flex gap-3">
                    <a href="{{ route('approval-chains.edit', $approvalChain) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all">
                        Edit
                    </a>
                    <form action="{{ route('approval-chains.destroy', $approvalChain) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this chain?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 hover:shadow-red-500/30 transition-all">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Stats & Rules -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Status Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">
                    <div class="absolute top-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-6">Configuration Rules</h3>
                        <dl class="space-y-6">
                            <div class="flex items-center justify-between group">
                                <dt class="flex items-center text-sm text-gray-500">
                                    <div
                                        class="p-2 rounded-lg bg-blue-50 text-blue-600 mr-3 group-hover:bg-blue-100 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                    Min Amount
                                </dt>
                                <dd class="text-sm font-bold text-gray-900">
                                    {{ $approvalChain->min_amount ? number_format($approvalChain->min_amount, 2) : 'N/A' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between group">
                                <dt class="flex items-center text-sm text-gray-500">
                                    <div
                                        class="p-2 rounded-lg bg-purple-50 text-purple-600 mr-3 group-hover:bg-purple-100 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 17h8m0 0V9m0 8l-8-8-4 4-6 6" />
                                        </svg>
                                    </div>
                                    Max Amount
                                </dt>
                                <dd class="text-sm font-bold text-gray-900">
                                    {{ $approvalChain->max_amount ? number_format($approvalChain->max_amount, 2) : 'N/A' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between group">
                                <dt class="flex items-center text-sm text-gray-500">
                                    <div
                                        class="p-2 rounded-lg bg-amber-50 text-amber-600 mr-3 group-hover:bg-amber-100 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                    </div>
                                    Min Discount
                                </dt>
                                <dd class="text-sm font-bold text-gray-900">
                                    {{ $approvalChain->min_discount_percentage ? number_format($approvalChain->min_discount_percentage, 1) . '%' : 'N/A' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Description Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {{ $approvalChain->description ?? 'No description provided for this chain.' }}
                    </p>
                </div>
            </div>

            <!-- Right Column: Flow -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Approval Sequence</h3>
                        <span
                            class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                            {{ $approvalChain->steps->count() }} Steps
                        </span>
                    </div>
                    <div class="p-8">
                        <div class="relative">
                            <!-- Connecting Line -->
                            @if($approvalChain->steps->count() > 1)
                                <div class="absolute left-6 top-6 bottom-6 w-0.5 bg-gray-100"></div>
                            @endif

                            <div class="space-y-8">
                                @forelse($approvalChain->steps as $step)
                                    <div class="relative flex items-center group">
                                        <!-- Node -->
                                        <div
                                            class="relative z-10 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 shadow-md shadow-indigo-200 group-hover:scale-110 transition-transform duration-300">
                                            <span class="text-white font-bold text-lg">{{ $step->order }}</span>
                                        </div>

                                        <!-- Content -->
                                        <div
                                            class="ml-6 flex-1 bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <h4 class="text-base font-bold text-gray-900">
                                                        {{ optional($step->user)->name ?? 'Unknown User' }}
                                                    </h4>
                                                    <p class="text-sm text-indigo-600 font-medium mt-0.5 capitalize">
                                                        {{ str_replace('_', ' ', $step->role) }}
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-2">
                                                        {{ optional($step->user)->email ?? 'No Email' }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-12">
                                        <p class="text-gray-500">No steps defined for this chain.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>