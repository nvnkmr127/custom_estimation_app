@extends('guide.layout')

@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Approvals & Inbox</h1>
        <p class="mt-4 text-lg text-slate-600">Managing the internal review process for high-value estimates.</p>
    </div>

    <!-- Use Case (Light Theme) -->
    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-8 shadow-sm mb-16 relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                    Case</span>
                <span class="text-teal-900 text-sm font-medium">Team Management</span>
            </div>
            <h3 class="text-2xl font-bold mb-3 text-teal-900 mt-0">Scenario: Manager Vacation</h3>
            <p class="text-teal-800 text-sm mb-6 max-w-xl leading-relaxed">
                When the primary approver is away, you need to ensure high-value estimates (e.g., > ₹5,00,000) don't get
                stuck.
            </p>
            <div class="flex flex-wrap gap-2 text-xs font-mono text-teal-700">
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">1. Delegated Access
                    (Admin)</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">2. Review via Mobile</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">3. Quick Approval</span>
            </div>
        </div>
    </div>

    <!-- Approval Inbox -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Approval Inbox</h2>
        <p class="text-slate-600 mb-8">When an estimator submits a proposal that exceeds their authorized limit, it appears
            here.</p>

        <div class="bg-white p-8 border border-slate-200 rounded-xl shadow-sm">
            <h4 class="text-lg font-bold text-slate-800 mb-6">Review Process</h4>
            <ol class="space-y-8 relative border-l border-slate-200 ml-3">
                <li class="ml-8">
                    <span
                        class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -left-4 ring-8 ring-white">
                        <svg class="w-4 h-4 text-blue-800" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </span>
                    <h3 class="flex items-center mb-2 text-lg font-semibold text-slate-900">Notification Received</h3>
                    <p class="text-base font-normal text-slate-600 leading-relaxed">You receive an email and a dashboard
                        alert: "Estimate #1234 requires approval".</p>
                </li>
                <li class="ml-8">
                    <span
                        class="absolute flex items-center justify-center w-8 h-8 bg-white border border-slate-300 rounded-full -left-4 ring-8 ring-white">
                        2
                    </span>
                    <h3 class="mb-2 text-lg font-semibold text-slate-900">Review Details</h3>
                    <p class="text-base font-normal text-slate-600 leading-relaxed">Click to view the full PDF draft. Check
                        margins, product availability, and discounts.</p>
                </li>
                <li class="ml-8">
                    <span
                        class="absolute flex items-center justify-center w-8 h-8 bg-white border border-slate-300 rounded-full -left-4 ring-8 ring-white">
                        3
                    </span>
                    <h3 class="mb-2 text-lg font-semibold text-slate-900">Decision</h3>
                    <p class="text-base font-normal text-slate-600 leading-relaxed">
                        <span class="text-green-600 font-bold">Approve:</span> Estimator is notified and can send to
                        client.<br>
                        <span class="text-red-500 font-bold">Reject:</span> Send back with comments for revision.
                    </p>
                </li>
            </ol>
        </div>
    </div>

    <!-- Notifications -->
    <div class="mb-16">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Notifications</h2>
        <table class="min-w-full divide-y divide-slate-200 border border-slate-200 rounded-lg">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Trigger Event
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Recipient</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Method</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                <tr>
                    <td class="px-6 py-5 text-sm font-bold text-slate-900">Estimate Viewed</td>
                    <td class="px-6 py-5 text-sm text-slate-600">Estimator</td>
                    <td class="px-6 py-5 text-sm text-slate-600">Email</td>
                </tr>
                <tr>
                    <td class="px-6 py-5 text-sm font-bold text-slate-900">Approval Requested</td>
                    <td class="px-6 py-5 text-sm text-slate-600">Approver (Manager)</td>
                    <td class="px-6 py-5 text-sm text-slate-600">Email + Bell Icon</td>
                </tr>
                <tr>
                    <td class="px-6 py-5 text-sm font-bold text-slate-900">Estimate Signed</td>
                    <td class="px-6 py-5 text-sm text-slate-600">Estimator + Admin</td>
                    <td class="px-6 py-5 text-sm text-slate-600">Email</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection