<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $webhook->name }}
            </h2>
            <a href="{{ route('admin.webhooks.edit', $webhook) }}"
                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Target URL</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-all">{{ $webhook->url }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $webhook->status }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">HTTP Method</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $webhook->http_method }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Events</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if (is_array($webhook->events))
                                {{ implode(', ', $webhook->events) }}
                            @else
                                {{ $webhook->events }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>

