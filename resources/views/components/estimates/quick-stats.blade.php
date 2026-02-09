@props(['estimate'])

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-4 rounded-2xl shadow-sm ring-1 ring-slate-200">
        <dt class="text-xs font-semibold text-slate-500 uppercase">Grand Total</dt>
        <dd class="mt-1 text-lg font-bold text-slate-900">{{ $estimate->currency }}
            {{ number_format($estimate->grand_total, 2) }}
        </dd>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm ring-1 ring-slate-200">
        <dt class="text-xs font-semibold text-slate-500 uppercase">Expiry Date</dt>
        <dd
            class="mt-1 text-lg font-bold {{ $estimate->expiry_date && $estimate->expiry_date < now() ? 'text-red-600' : 'text-slate-900' }}">
            {{ $estimate->expiry_date ? $estimate->expiry_date->format('M d, Y') : 'No Date Set' }}
        </dd>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm ring-1 ring-slate-200">
        <dt class="text-xs font-semibold text-slate-500 uppercase">Client Views</dt>
        <dd class="mt-1 text-lg font-bold text-slate-900">{{ $estimate->view_count }}</dd>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm ring-1 ring-slate-200">
        <dt class="text-xs font-semibold text-slate-500 uppercase">Last Activity</dt>
        <dd class="mt-1 text-sm font-medium text-slate-700">
            {{ $estimate->updated_at->diffForHumans() }}
        </dd>
    </div>
</div>