@props(['status'])

@php
    $statusStyles = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-600/20',
        'sent' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
        'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/10',
        'expired' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'waiting_approval' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/10',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset ' . ($statusStyles[$status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10')]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>