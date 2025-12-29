@extends('guide.layout')

@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Reports & Analytics</h1>
        <p class="mt-4 text-lg text-slate-600">Visual insights into your sales performance.</p>
    </div>

    <!-- Use Case (Light Theme) -->
    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-8 shadow-sm mb-16 relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                    Case</span>
                <span class="text-teal-900 text-sm font-medium">Payroll</span>
            </div>
            <h3 class="text-2xl font-bold mb-3 text-teal-900 mt-0">Scenario: Monthly Commissions</h3>
            <p class="text-teal-800 text-sm mb-6 max-w-xl leading-relaxed">
                It's end of month. You need to calculate 5% commission for all estimates "Accepted" this month.
            </p>
            <div class="flex flex-wrap gap-2 text-xs font-mono text-teal-700">
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">1. Filter Date Range (This
                    Month)</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">2. Filter Status
                    "Accepted"</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">3. Download CSV</span>
            </div>
        </div>
    </div>

    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Exporting Data</h2>
        <p class="text-slate-600 mb-8">You can export raw data for external analysis or accounting.</p>

        <div class="bg-white border border-slate-200 rounded-lg p-8">
            <h4 class="font-bold text-slate-900 mb-6 text-lg">Available Reports</h4>
            <ul class="space-y-6">
                <li class="flex items-start">
                    <div class="bg-green-100 p-3 rounded-lg mr-5">
                        <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <strong class="block text-slate-900 text-base mb-1">Sales Summary (CSV)</strong>
                        <span class="text-sm text-slate-500 leading-relaxed">Totals by client, month, and status. Best for
                            commission calculation.</span>
                    </div>
                </li>
                <li class="flex items-start">
                    <div class="bg-blue-100 p-3 rounded-lg mr-5">
                        <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <strong class="block text-slate-900 text-base mb-1">Conversion Metrics</strong>
                        <span class="text-sm text-slate-500 leading-relaxed">View your "Win Rate" (Accepted / Total Sent)
                            over time.</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
@endsection