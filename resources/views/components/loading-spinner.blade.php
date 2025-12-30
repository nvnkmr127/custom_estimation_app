@props(['size' => '5', 'color' => 'white'])

@php
    $sizes = [
        '3' => 'h-3 w-3',
        '4' => 'h-4 w-4',
        '5' => 'h-5 w-5',
        '6' => 'h-6 w-6',
        '8' => 'h-8 w-8',
    ];
    $sizeClass = $sizes[(string) $size] ?? 'h-5 w-5';

    $colors = [
        'white' => 'text-white',
        'indigo' => 'text-indigo-600',
        'slate' => 'text-slate-600',
        'red' => 'text-red-600',
    ];
    $colorClass = $colors[(string) $color] ?? 'text-white';
@endphp

<svg {{ $attributes->merge(['class' => "animate-spin $sizeClass $colorClass"]) }} xmlns="http://www.w3.org/2000/svg"
    fill="none" viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
    </path>
</svg>