@extends('guide.layout')

@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Admin Configuration</h1>
        <p class="mt-4 text-lg text-slate-600">Global settings for Super Admins.</p>
    </div>

    <!-- Use Case (Light Theme) -->
    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-8 shadow-sm mb-16 relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                    Case</span>
                <span class="text-teal-900 text-sm font-medium">HR Onboarding</span>
            </div>
            <h3 class="text-2xl font-bold mb-3 text-teal-900 mt-0">Scenario: New Estimator</h3>
            <p class="text-teal-800 text-sm mb-6 max-w-xl leading-relaxed">
                You've just hired a new estimator, Sarah. She needs limited access to create estimates but cannot delete
                them or see admin reports.
            </p>
            <div class="flex flex-wrap gap-2 text-xs font-mono text-teal-700">
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">1. Create User "Sarah"</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">2. Assign Role
                    "Estimator"</span>
                <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">3. Set Approval Limit
                    ₹5,00,000</span>
            </div>
        </div>
    </div>

    <!-- Room Templates -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Room Templates</h2>
        <p class="text-slate-600 mb-6">Define standard bundles of items (e.g., "Master Bedroom Request") to speed up the
            estimation process.</p>
        <div class="bg-white border border-slate-200 rounded-lg p-8 text-sm shadow-sm">
            <strong class="text-lg text-slate-900 block mb-4">How to configure:</strong>
            <ol class="list-decimal list-inside space-y-3 text-slate-600">
                <li>Go to <strong>Templates > New</strong>.</li>
                <li>Name the template (e.g., "Bathroom Basic").</li>
                <li>Add all standard line items (Sink, Toilet, Labor Hours).</li>
                <li>Save. Now estimators can select this from the dropdown to auto-populate their quote.</li>
            </ol>
        </div>
    </div>

    <!-- PDF Templates -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">PDF Templates</h2>
        <p class="text-slate-600 mb-6">Customize the look and feel of the documents sent to clients.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
                class="bg-white border border-slate-200 p-6 rounded-lg text-center hover:bg-slate-50 cursor-pointer shadow-sm">
                <strong class="block text-slate-900 mb-2">Modern</strong>
                <span class="text-xs text-slate-500">Clean, sans-serif fonts. Default.</span>
            </div>
            <div
                class="bg-white border border-slate-200 p-6 rounded-lg text-center hover:bg-slate-50 cursor-pointer shadow-sm">
                <strong class="block text-slate-900 mb-2">Classic</strong>
                <span class="text-xs text-slate-500">Serif fonts, formal layout.</span>
            </div>
            <div
                class="bg-white border border-slate-200 p-6 rounded-lg text-center hover:bg-slate-50 cursor-pointer shadow-sm">
                <strong class="block text-slate-900 mb-2">Minimal</strong>
                <span class="text-xs text-slate-500">Focus on data, less styling.</span>
            </div>
        </div>
    </div>

    <!-- Users & Permissions -->
    <div class="mb-16">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Users & Approvals</h2>

        <div class="space-y-10">
            <div>
                <h4 class="font-bold text-slate-900 mb-2">Permissions</h4>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Use the <strong>Permissions Matrix</strong> to toggle specific actions (e.g., "Can Delete Estimate") for
                    each role.
                </p>
            </div>
            <div>
                <h4 class="font-bold text-slate-900 mb-2">Approval Chains</h4>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Define logic: "If Estimate Total > ₹10,00,000, Require Approval from [Manager Name]".
                </p>
                <div class="bg-red-50 text-red-800 p-4 rounded text-sm mt-3 border border-red-100">
                    <strong>Warning:</strong> Deleting a user who is part of an active chain will pause approvals for their
                    subordinates.
                </div>
            </div>
        </div>
    </div>
@endsection