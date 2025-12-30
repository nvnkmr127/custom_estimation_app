<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $approvalChain->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Approval Chain Details</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <a href="{{ route('approval-chains.edit', $approvalChain) }}"
                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all duration-200">
                Edit Chain
            </a>
            <a href="{{ route('approval-chains.index') }}"
                class="rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all duration-200">
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Basic Info -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Chain Information</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Chain Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $approvalChain->name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($approvalChain->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-700 ring-1 ring-inset ring-green-700/10">Active</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-700/10">Inactive</span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $approvalChain->description ?? 'No description provided.' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Steps -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Approval Steps</h3>
                </div>
                <div class="px-6 py-5">
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach($approvalChain->steps as $step)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span
                                                    class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center ring-8 ring-white">
                                                    <span
                                                        class="text-xs font-bold text-indigo-600">{{ $step->order }}</span>
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-900">
                                                        Approved by <span
                                                            class="font-medium text-gray-900">{{ $step->user->name }}</span>
                                                    </p>
                                                    <p class="text-xs text-gray-500 capitalize">Role:
                                                        {{ str_replace('_', ' ', $step->role) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Stats -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Statistics</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Total Steps</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $approvalChain->steps->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Associated Estimates</dt>
                            <!-- Placeholder if relationship not loaded/exists easily -->
                            <dd class="text-sm font-medium text-gray-900">-</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>