<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between mb-10">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">
                    Approval Chains
                </h1>
                <p class="mt-2 text-sm text-gray-500">
                    Manage and orchestrate your estimate approval workflows with precision.
                </p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0">
                <a href="{{ route('approval-chains.create') }}"
                   class="group inline-flex items-center justify-center rounded-full bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-500 hover:shadow-indigo-500/50 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="-ml-1 mr-2 h-5 w-5 transition-transform duration-300 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create New Chain
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.opacity class="mb-8 rounded-lg bg-green-50 p-4 border border-green-100 shadow-sm relative">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="absolute top-4 right-4 text-green-600 hover:text-green-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition.opacity class="mb-8 rounded-lg bg-red-50 p-4 border border-red-100 shadow-sm relative">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="absolute top-4 right-4 text-red-600 hover:text-red-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Grid -->
        <div class="grid grid-cols-1 gap-8">
            @forelse($chains as $chain)
                <div class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 overflow-hidden">
                    <!-- Status Bar -->
                    <div class="absolute top-0 left-0 w-1 h-full {{ $chain->is_active ? 'bg-gradient-to-b from-green-400 to-emerald-500' : 'bg-gray-200' }}"></div>

                    <div class="p-6 sm:p-8">
                        <div class="sm:flex sm:items-center sm:justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-4">
                                    <h2 class="text-xl font-bold text-gray-900 truncate group-hover:text-indigo-600 transition-colors">
                                        {{ $chain->name }}
                                    </h2>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $chain->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        @if($chain->is_active)
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span> Active
                                        @else
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1.5"></span> Inactive
                                        @endif
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                                    {{ $chain->description ?? 'No description provided.' }}
                                </p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-4 sm:mt-0 flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 transform translate-x-4 group-hover:translate-x-0">
                                <a href="{{ route('approval-chains.edit', $chain) }}" class="text-gray-400 hover:text-indigo-600 bg-gray-50 hover:bg-indigo-50 p-2 rounded-full transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                                <form action="{{ route('approval-chains.destroy', $chain) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this approval chain?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 bg-gray-50 hover:bg-red-50 p-2 rounded-full transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Rules Grid -->
                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-gray-100 pt-6">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Min Amount</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $chain->min_amount ? $chain->currency . ' ' . number_format($chain->min_amount, 2) : 'No Limit' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Max Amount</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $chain->max_amount ? $chain->currency . ' ' . number_format($chain->max_amount, 2) : 'No Limit' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase">Min Discount</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $chain->min_discount_percentage ? number_format($chain->min_discount_percentage, 1) . '%' : 'None' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Visual Flow -->
                        <div class="mt-8">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Approval Sequence</h4>
                            <div class="flex items-center flex-wrap gap-2">
                                @forelse($chain->steps as $step)
                                    <div class="flex items-center">
                                        <div class="relative flex items-center group/tooltip">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold border-2 border-white ring-1 ring-indigo-50 shadow-sm relative z-10 transition-transform hover:scale-110">
                                                {{ $step->order }}
                                            </div>
                                            <div class="hidden sm:block absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover/tooltip:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none">
                                                {{ $step->user->name }}
                                                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                                            </div>
                                            <span class="ml-2 text-sm font-medium text-gray-700">{{ Str::limit($step->user->name, 15) }}</span>
                                        </div>
                                        @if(!$loop->last)
                                            <div class="w-8 h-0.5 bg-gray-200 mx-2"></div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-sm text-gray-400 italic">No steps defined</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Link -->
                    <a href="{{ route('approval-chains.show', $chain) }}" class="block px-6 py-3 bg-gray-50 border-t border-gray-100 text-center text-sm font-medium text-indigo-600 hover:text-indigo-700 hover:bg-gray-100 transition-colors">
                        View Full Details &rarr;
                    </a>
                </div>
            @empty
                <div class="text-center py-20 bg-white rounded-3xl shadow-sm border border-dashed border-gray-300">
                    <div class="mx-auto h-24 w-24 rounded-full bg-indigo-50 flex items-center justify-center mb-6">
                        <svg class="h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="mt-2 text-xl font-bold text-gray-900">No approval chains yet</h3>
                    <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">Get started by building your first approval workflow to streamline your estimation process.</p>
                    <div class="mt-8">
                        <a href="{{ route('approval-chains.create') }}" class="inline-flex items-center rounded-full bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-500 hover:shadow-indigo-500/50">
                            <svg class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Create Approval Chain
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>