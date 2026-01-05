<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Approval Chains</h1>
            <p class="mt-2 text-sm text-slate-500">Manage approval workflows for estimates.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('approval-chains.create') }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all duration-200">
                Create Approval Chain
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                        clip-rule="evenodd" />
                </svg>
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6">
        @forelse($chains as $chain)
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $chain->name }}</h3>
                                @if($chain->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 ring-1 ring-inset ring-green-700/10">
                                        Active
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-700/10">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                            @if($chain->description)
                                <p class="mt-1 text-sm text-gray-500">{{ $chain->description }}</p>
                            @endif
                            <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                @if($chain->min_amount)
                                    <span
                                        class="inline-flex items-center gap-1 rounded bg-gray-50 px-2 py-1 ring-1 ring-inset ring-gray-600/10">
                                        Min: {{ $chain->currency }} {{ number_format($chain->min_amount, 2) }}
                                    </span>
                                @endif
                                @if($chain->max_amount)
                                    <span
                                        class="inline-flex items-center gap-1 rounded bg-gray-50 px-2 py-1 ring-1 ring-inset ring-gray-600/10">
                                        Max: {{ $chain->currency }} {{ number_format($chain->max_amount, 2) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                {{ $chain->steps_count }} {{ Str::plural('step', $chain->steps_count) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50">
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Approval Flow</h4>
                            <div class="flex items-center gap-2 flex-wrap">
                                @foreach($chain->steps as $step)
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-200">
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                                {{ $step->order }}
                                            </span>
                                            <span class="font-medium text-gray-900">{{ $step->user->name }}</span>
                                            <span
                                                class="text-gray-500">({{ ucfirst(str_replace('_', ' ', $step->role)) }})</span>
                                        </div>
                                        @if(!$loop->last)
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-white border-t border-gray-200">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('approval-chains.show', $chain) }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            View
                        </a>
                        <a href="{{ route('approval-chains.edit', $chain) }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Edit
                        </a>
                        <form action="{{ route('approval-chains.destroy', $chain) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('Are you sure you want to delete this approval chain?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="text-sm font-medium text-rose-600 hover:text-rose-500 bg-transparent border-0 cursor-pointer p-0">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No approval chains</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new approval chain.</p>
                <div class="mt-6">
                    <a href="{{ route('approval-chains.create') }}"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        New Approval Chain
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</x-app-layout>