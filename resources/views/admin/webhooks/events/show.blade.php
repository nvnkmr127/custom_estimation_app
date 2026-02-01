<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Event Details') }} <span class="text-gray-400 text-base font-normal">#{{ $event->id }}</span>
            </h2>
            <a href="{{ route('admin.webhooks.events.index') }}"
                class="text-sm text-indigo-600 hover:text-indigo-900">Back to List</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('admin.webhooks.events.show', ['event' => $event])
        </div>
    </div>
</x-app-layout>