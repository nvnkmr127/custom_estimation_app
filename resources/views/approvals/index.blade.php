<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between mb-10">
            <div>
                <h1
                    class="text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">
                    Approval Inbox
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    Review and act on estimates waiting for your approval.
                </p>
            </div>
            <!-- Optional: filter or sort controls could go here -->
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($pendingEstimates as $estimate)
                <div
                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 flex flex-col h-full">
                    <!-- Top Stripe -->
                    <div
                        class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 to-orange-500 rounded-t-2xl">
                    </div>

                    <div class="p-6 flex-1 flex flex-col">
                        <!-- Header: Estimate # and Date -->
                        <div class="flex items-center justify-between mb-4">
                            <span
                                class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                Pending Review
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $estimate->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <!-- Main Content -->
                        <div class="mb-4">
                            <h3
                                class="text-lg font-bold text-gray-900 line-clamp-1 group-hover:text-indigo-600 transition-colors">
                                <a href="{{ route('estimates.show', $estimate) }}">
                                    <span class="absolute inset-0"></span>
                                    {{ $estimate->title }}
                                </a>
                            </h3>
                            <div class="mt-1 flex items-center text-sm text-gray-500">
                                <svg class="mr-1.5 h-4 w-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                #{{ $estimate->estimate_number }}
                            </div>
                        </div>

                        <!-- Client Info -->
                        <div class="flex items-center gap-3 mb-6 p-3 bg-gray-50 rounded-lg">
                            <div
                                class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                                {{ substr($estimate->client->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $estimate->client->name ?? 'Unknown Client' }}
                                </p>
                                <p class="text-xs text-gray-500 truncate">
                                    Client
                                </p>
                            </div>
                        </div>

                        <!-- Footer: Amount & Action -->
                        <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-medium">Grand Total</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ number_format($estimate->grand_total, 2) }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span
                                    class="inline-flex items-center justify-center rounded-full bg-indigo-50 p-2 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="text-center py-20 bg-white rounded-3xl shadow-sm border border-dashed border-gray-300">
                        <div class="mx-auto h-24 w-24 rounded-full bg-green-50 flex items-center justify-center mb-6">
                            <svg class="h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-2 text-xl font-bold text-gray-900">All caught up!</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">
                            You have no pending estimates to review at this time.
                        </p>
                        <div class="mt-8">
                            <a href="{{ route('dashboard') }}"
                                class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $pendingEstimates->links() }}
        </div>
    </div>
</x-app-layout>