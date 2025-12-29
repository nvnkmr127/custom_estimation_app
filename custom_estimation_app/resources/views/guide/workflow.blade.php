@extends('guide.layout')

@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Workflow & Logs</h1>
        <p class="mt-4 text-lg text-slate-600">Track every action and never miss a follow-up.</p>
    </div>

    <!-- Use Case (Light Theme) -->
    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-8 shadow-sm mb-16 relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                    Case</span>
                <span class="text-teal-900 text-sm font-medium">Compliance</span>
            </div>
            <h3 class="text-2xl font-bold mb-3 text-teal-900 mt-0">Scenario: Client Dispute</h3>
            <p class="text-teal-800 text-sm mb-6 max-w-xl leading-relaxed">
                A client claims they never received the estimate email you sent. You need to prove the system sent it.
            </p>
            <div class="flex flex-wrap gap-2 text-xs font-mono text-teal-700">
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">1. Open Estimate Detail</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">2. View Activity Log</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">3. Screenshot & Email
                    Client</span>
            </div>
        </div>
    </div>

    <!-- Activity Logs -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Activity Logs</h2>
        <div class="bg-white border border-slate-200 p-8 rounded-xl shadow-sm">
            <p class="text-slate-600 mb-6 leading-relaxed">
                The system maintains an immutable audit trail for every estimate. This is critical for dispute resolution
                and compliance.
            </p>
            <ul class="space-y-4">
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-slate-400 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <strong class="block text-slate-900">Creation</strong>
                        <span class="text-sm text-slate-500">Who created the draft and when.</span>
                    </div>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-slate-400 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <div>
                        <strong class="block text-slate-900">Views</strong>
                        <span class="text-sm text-slate-500">Exact timestamp when the client opened the link (via Tracking
                            Pixel).</span>
                    </div>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-slate-400 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                    <div>
                        <strong class="block text-slate-900">Signature</strong>
                        <span class="text-sm text-slate-500">IP Address and user agent of the signer.</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <!-- Reminders -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Reminders</h2>
        <p class="text-slate-600 mb-8">Set automated reminders to follow up on pending estimates.</p>

        <div class="grid gap-8 md:grid-cols-2">
            <div class="bg-white border border-slate-200 p-6 rounded-lg shadow-sm">
                <h4 class="font-bold text-slate-800 mb-2">Automated Follow-ups</h4>
                <p class="text-sm text-slate-600 leading-relaxed">
                    The system can automatically email the client ("Just checking in...") 3 days after sending if no action
                    has been taken.
                </p>
            </div>
            <div class="bg-white border border-slate-200 p-6 rounded-lg shadow-sm">
                <h4 class="font-bold text-slate-800 mb-2">Internal Tasks</h4>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Create personal tasks linked to an estimate (e.g., "Call John about the kitchen sink choice") via the
                    sidebar in the Estimate View.
                </p>
            </div>
        </div>
    </div>
@endsection