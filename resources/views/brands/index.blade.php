<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Brands') }}
        </h2>
    </x-slot>

    <livewire:brands.brand-manager />
</x-app-layout>