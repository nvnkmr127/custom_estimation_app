@props(['loading' => false])

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }} {{ $loading ? 'disabled' : '' }}>
    @if($loading)
        <x-loading-spinner size="4" class="mr-2" />
    @endif
    {{ $slot }}
</button>