<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Experiment Results') }}: {{ $experiment->name }}
            </h2>
            <div class="flex space-x-3">
                @if($experiment->status === 'draft' || $experiment->status === 'paused')
                    <form action="{{ route('automation.experiments.start', $experiment->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Start Experiment
                        </button>
                    </form>
                @endif

                @if($experiment->status === 'running')
                    <form action="{{ route('automation.experiments.stop', $experiment->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pause Experiment
                        </button>
                    </form>
                @endif

                <a href="{{ route('automation.experiments.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 active:text-gray-800 active:bg-gray-50 transition ease-in-out duration-150">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Metadata Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Status</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900 capitalize">{{ $experiment->status }}</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Trigger Event</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $experiment->trigger_event }}</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Started At</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            {{ $experiment->started_at ? \Carbon\Carbon::parse($experiment->started_at)->format('M d, H:i') : '-' }}
                        </dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Traffic</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $variants->sum('executions_count') }}
                            runs</dd>
                    </div>
                </div>
            </div>

            <!-- Stats/Confidence (New) -->
            @if(isset($confidence) && $variants->count() == 2)
                <div class="mb-8 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <!-- Heroicon name: solid/information-circle -->
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Statistical Confidence
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>
                                    Confidence that Variant B (Test) is performing differently than Variant A (Control): <strong>{{ number_format($confidence, 1) }}%</strong>.
                                    @if($confidence > 95)
                                        <span class="block mt-1 font-bold text-green-700">Winner Found! (95%+ Confidence)</span>
                                    @else
                                        <span class="block mt-1 text-gray-600">Collecting more data...</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Variant Comparison -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Variant Performance</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @foreach($variants as $index => $variant)
                            <div
                                class="border rounded-lg p-6 relative overflow-hidden {{ $index === 0 ? 'bg-gray-50 border-gray-200' : 'bg-indigo-50 border-indigo-200' }}">
                                <div class="absolute top-0 right-0 p-4 opacity-10">
                                    <span class="text-9xl font-bold">{{ $index === 0 ? 'A' : 'B' }}</span>
                                </div>

                                <h4 class="text-xl font-bold {{ $index === 0 ? 'text-gray-800' : 'text-indigo-800' }} mb-2">
                                    {{ $index === 0 ? 'Control (Variant A)' : 'Test (Variant B)' }}
                                </h4>
                                <p class="text-sm text-gray-600 mb-6 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    {{ $variant->automation_name }}
                                </p>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500">Traffic Allocation</span>
                                        <span class="block text-2xl font-bold text-gray-900">{{ $variant->weight }}%</span>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-500">Real Traffic</span>
                                        <span class="block text-2xl font-bold text-gray-900">
                                            @php
                                                $total = $variants->sum('executions_count');
                                                $percent = $total > 0 ? round(($variant->executions_count / $total) * 100) : 0;
                                            @endphp
                                            {{ $percent }}%
                                        </span>
                                    </div>
                                </div>

                                <hr class="my-6 border-gray-300">

                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Total Executions</span>
                                    <div class="flex items-end">
                                        <span
                                            class="text-4xl font-extrabold text-gray-900">{{ $variant->executions_count }}</span>
                                        <span class="ml-2 text-sm text-gray-500 mb-1">runs</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>