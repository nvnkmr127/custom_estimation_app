@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'disabled' => false,
    'type' => 'button',
    'href' => null
])

@php
$baseClasses = 'inline-flex items-center justify-center font-bold transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2';

$variantClasses = [
    'primary' => 'bg-indigo-600 text-white shadow-md shadow-indigo-200 hover:bg-indigo-700 focus:ring-indigo-500',
    'secondary' => 'bg-white text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-50 hover:border-slate-300 focus:ring-slate-500',
    'danger' => 'bg-rose-600 text-white shadow-md shadow-rose-200 hover:bg-rose-700 focus:ring-rose-500',
    'success' => 'bg-emerald-600 text-white shadow-md shadow-emerald-200 hover:bg-emerald-700 focus:ring-emerald-500',
    'warning' => 'bg-amber-600 text-white shadow-md shadow-amber-200 hover:bg-amber-700 focus:ring-amber-500',
    'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-500',
    'link' => 'text-indigo-600 hover:text-indigo-800 underline-offset-4 hover:underline focus:ring-indigo-500',
][$variant];

$sizeClasses = [
    'xs' => 'px-2.5 py-1.5 text-xs rounded-lg gap-1.5',
    'sm' => 'px-3 py-2 text-xs rounded-lg gap-2',
    'md' => 'px-4 py-2.5 text-sm rounded-xl gap-2',
    'lg' => 'px-6 py-3 text-base rounded-xl gap-2.5',
    'xl' => 'px-8 py-4 text-lg rounded-2xl gap-3',
][$size];

$classes = $baseClasses . ' ' . $variantClasses . ' ' . $sizeClasses;
$isDisabled = $disabled || $loading;
@endphp

@if($href && !$isDisabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Processing...</span>
        @else
            {{ $slot }}
        @endif
    </a>
@else
    <button 
        type="{{ $type }}" 
        {{ $attributes->merge(['class' => $classes]) }}
        @if($isDisabled) disabled @endif
    >
        @if($loading)
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Processing...</span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
