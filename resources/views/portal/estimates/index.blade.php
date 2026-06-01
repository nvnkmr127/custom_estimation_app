<x-portal-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Internal Preview Banner -->
        <div class="mb-8 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="ml-3 flex-1 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-amber-800 uppercase tracking-widest">Internal Preview Mode</h3>
                        <div class="mt-1 text-sm text-amber-700">
                            <p>You are viewing the master estimate list. This page is only visible to internal team members.</p>
                        </div>
                    </div>
                    
                    @if(app()->environment('local'))
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black text-amber-600 uppercase tracking-widest">Role Switcher:</span>
                            <div class="flex gap-1">
                                <a href="{{ route('dev.login') }}" class="px-2 py-1 bg-amber-200/50 hover:bg-amber-200 text-amber-800 rounded text-[9px] font-black uppercase tracking-tighter transition-colors">Admin</a>
                                @php
                                    $salesUser = \App\Models\User::where('role', 'sales')->first();
                                @endphp
                                @if($salesUser)
                                    <a href="{{ route('dev.login', $salesUser->id) }}" class="px-2 py-1 bg-amber-200/50 hover:bg-amber-200 text-amber-800 rounded text-[9px] font-black uppercase tracking-tighter transition-colors">Sales</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-slate-100">
            <div class="bg-slate-900 px-8 py-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight">Master Estimate List</h1>
                        @if(isset($client) && $client)
                            <div class="flex items-center gap-2 mt-2">
                                <p class="text-slate-400 font-medium">Internal preview of estimates for <span class="text-indigo-400 font-bold">{{ $client->name }}</span>.</p>
                                <a href="{{ route('portal.preview-list') }}" class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 text-[10px] font-black uppercase tracking-widest transition-all">
                                    Clear Filter
                                </a>
                            </div>
                        @else
                            <p class="text-slate-400 mt-2 font-medium">Internal preview of all created estimates in the system.</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="bg-white/10 px-4 py-2 rounded-2xl backdrop-blur-sm border border-white/10">
                            <span class="text-white/60 text-xs font-bold uppercase tracking-widest block">Total Records</span>
                            <span class="text-white text-xl font-black">{{ $estimates->total() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-8 py-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Estimate</th>
                            <th class="px-8 py-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Client</th>
                            <th class="px-8 py-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Amount</th>
                            <th class="px-8 py-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($estimates as $estimate)
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900">#{{ $estimate->estimate_number }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight mt-0.5">Created {{ $estimate->created_at->format('M d, Y') }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                            {{ strtoupper(substr(optional($estimate->client)->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800">{{ optional($estimate->client)->name ?? 'Valued Client' }}</span>
                                            <span class="text-[10px] text-slate-400 font-medium">{{ optional($estimate->client)->email ?? 'No Email' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900">{{ $estimate->currency_symbol }}{{ number_format($estimate->grand_total, 2) }}</span>
                                        @if($estimate->discount_total > 0)
                                            <span class="text-[9px] font-black text-amber-600 uppercase tracking-tighter mt-0.5">-{{ $estimate->currency_symbol }}{{ number_format($estimate->discount_total, 0) }} SAVED</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @php
                                        $statusClasses = [
                                            'draft' => 'bg-slate-100 text-slate-600',
                                            'sent' => 'bg-blue-50 text-blue-600',
                                            'viewed' => 'bg-indigo-50 text-indigo-600',
                                            'accepted' => 'bg-emerald-50 text-emerald-600',
                                            'declined' => 'bg-rose-50 text-rose-600',
                                            'expired' => 'bg-amber-50 text-amber-600',
                                            'waiting' => 'bg-orange-50 text-orange-600',
                                            'approved' => 'bg-emerald-50 text-emerald-600',
                                            'rejected' => 'bg-rose-50 text-rose-600',
                                        ];
                                        
                                        $clientStatus = strtolower($estimate->client_status ?: 'not_sent');
                                        $internalStatus = strtolower($estimate->estimate_status);
                                        $approvalStatus = strtolower($estimate->approval_status);
                                        
                                        $clientClass = $statusClasses[$clientStatus] ?? 'bg-slate-100 text-slate-600';
                                        $internalClass = $statusClasses[$internalStatus] ?? 'bg-slate-100 text-slate-600';
                                    @endphp
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex flex-col">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mb-0.5">Client Status</span>
                                            <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest {{ $clientClass }}">
                                                {{ $clientStatus }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col border-t border-slate-100 pt-1.5">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mb-0.5">Internal Status</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest {{ $internalClass }}">
                                                    {{ $internalStatus }}
                                                </span>
                                                @if($approvalStatus && $approvalStatus !== 'not_required')
                                                    <span class="text-[8px] font-bold text-slate-400 uppercase">({{ $approvalStatus }})</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ URL::signedRoute('portal.show', $estimate) }}" 
                                           class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-700 uppercase tracking-widest hover:border-indigo-500 hover:text-indigo-600 transition-all shadow-sm"
                                           title="Preview Client View">
                                            Preview
                                        </a>
                                        
                                        <div class="flex items-center gap-1">
                                            <form action="{{ route('estimates.copy', $estimate) }}" method="POST" class="inline" onsubmit="return confirm('Duplicate this estimate as a new record?')">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-[10px] font-black text-slate-600 uppercase tracking-widest hover:bg-slate-100 hover:text-slate-900 transition-all shadow-sm"
                                                        title="Duplicate this estimate as a new one">
                                                    Duplicate
                                                </button>
                                            </form>

                                            <form action="{{ route('estimates.version', $estimate) }}" method="POST" class="inline" onsubmit="return confirm('Create a new sequential version (e.g. v2, v3) of this estimate?')">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-600 uppercase tracking-widest hover:border-indigo-500 hover:text-indigo-700 transition-all shadow-sm"
                                                        title="Create a new version (e.g. v2, v3)">
                                                    Version
                                                </button>
                                            </form>

                                            <a href="{{ route('estimates.edit', $estimate) }}" 
                                               class="inline-flex items-center px-3 py-1.5 bg-indigo-600 rounded-xl text-[10px] font-black text-white uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-slate-500 font-bold">No estimates found in the system.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($estimates->hasPages())
                <div class="px-8 py-6 bg-slate-50 border-t border-slate-200">
                    {{ $estimates->links() }}
                </div>
            @endif
        </div>
        
        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                &larr; Back to Admin Dashboard
            </a>
        </div>
    </div>
</x-portal-layout>
