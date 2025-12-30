@props(['padding' => '6'])

@php
    $paddingClasses = [
        '0' => 'p-0',
        '4' => 'p-4',
        '6' => 'p-6',
        '8' => 'p-8',
    ];
    $paddingClass = $paddingClasses[(string) $padding] ?? 'p-6';
@endphp

<div {{ $attributes->merge(['class' => "bg-white shadow-sm ring-1 ring-slate-900/5 rounded-xl overflow-hidden $paddingClass"]) }}>
    {{ $slot }}
</div>