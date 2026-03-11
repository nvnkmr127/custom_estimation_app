@extends('guide.layout')



@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Estimates Workflow</h1>
        <p class="mt-4 text-lg text-slate-600">The core function of the application: creating, managing, and finalizing
            proposals.</p>
    </div>

    <!-- Creating Estimates Deep Dive -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Creating Estimates</h2>

        <!-- Use Case Card (Light Theme) -->
        <div class="bg-teal-50 border border-teal-200 rounded-2xl p-8 shadow-sm mb-16 relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-4">
                    <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                        Case</span>
                    <span class="text-teal-900 text-sm font-medium">Residential Project</span>
                </div>
                <h3 class="text-2xl font-bold mb-3 text-teal-900 mt-0">Scenario: Kitchen Remodel (₹1,50,000)</h3>
                <p class="text-teal-800 text-sm mb-6 max-w-xl leading-relaxed">
                    Client "John Doe" wants a full kitchen renovation. You need to create an estimate with cabinetry,
                    countertops, and labor, then send it for digital signature.
                </p>
                <div class="flex flex-wrap gap-2 text-xs font-mono text-teal-700">
                    <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">1. Select "Kitchen
                        Template"</span>
                    <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">2. Add "Granite
                        Countertop"</span>
                    <span class="bg-white px-3 py-1.5 rounded border border-teal-100 shadow-sm">3. Send via Email</span>
                </div>
            </div>
        </div>

        <div class="space-y-16">
            <div class="flex gap-8">
                <div class="flex flex-col items-center">
                    <div
                        class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-blue-200">
                        1</div>
                    <div class="w-0.5 h-full bg-slate-200 my-4"></div>
                </div>
                <div class="pb-8">
                    <h4 class="text-xl font-bold text-slate-900 mb-3">Initiate & Client Selection</h4>
                    <p class="text-slate-600 mb-4">Click <span class="font-semibold text-slate-900">New Estimate</span>. In
                        the client dropdown, search for the client.</p>
                    <div class="bg-slate-50 p-6 rounded-lg text-sm border border-slate-200">
                        <strong class="text-slate-800 block mb-2">Form Field: Client</strong>
                        <span class="text-slate-600">This is a live search field. It queries the local client database. Select the client to auto-fill their address.</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-8">
                <div class="flex flex-col items-center">
                    <div
                        class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-blue-200">
                        2</div>
                    <div class="w-0.5 h-full bg-slate-200 my-4"></div>
                </div>
                <div class="pb-8">
                    <h4 class="text-xl font-bold text-slate-900 mb-3">Add Line Items</h4>
                    <p class="text-slate-600 mb-4">You can add items manually or use the <strong>Product Picker</strong>.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            class="bg-white p-6 border border-slate-200 rounded-lg shadow-sm hover:border-blue-300 transition-colors">
                            <h5 class="font-bold text-slate-800 mb-2">Room Templates</h5>
                            <p class="text-sm text-slate-500 leading-relaxed">Pre-load a set of items (e.g., "Standard
                                Bathroom") to save time.</p>
                        </div>
                        <div
                            class="bg-white p-6 border border-slate-200 rounded-lg shadow-sm hover:border-blue-300 transition-colors">
                            <h5 class="font-bold text-slate-800 mb-2">AI Assist</h5>
                            <p class="text-sm text-slate-500 leading-relaxed">Click the "Sparkles" icon to have AI write a
                                professional product description.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-8">
                <div class="flex flex-col items-center">
                    <div
                        class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-blue-200">
                        3</div>
                    <div class="w-0.5 bg-transparent my-4"></div>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-slate-900 mb-3">Review & Send</h4>
                    <p class="text-slate-600 mb-4">Check the totals. Once approved, clicking <strong>Send</strong> emails a
                        secure link to the client.</p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mt-4">
                        <p class="text-sm text-yellow-800 leading-relaxed"><strong>Note on Approvals:</strong> If the total
                            exceeds your approval limit, the "Send" button will change to "Request Approval". The estimate
                            cannot be sent until a supervisor approves it.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 pt-16 mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Digital Signatures</h2>
        <p class="text-slate-600 mb-6 font-medium">Clients receive a secure link to view the proposal. They can sign
            directly on their phone or computer.</p>
        <ul class="list-disc list-inside space-y-4 text-slate-600 bg-slate-50 p-8 rounded-xl border border-slate-200">
            <li><strong class="text-slate-800">Validity:</strong> The signature is captured with a timestamp and IP address
                for audit trails.</li>
            <li><strong class="text-slate-800">Automation:</strong> Once signed, the status changes to "Accepted"
                automatically.</li>
            <li><strong class="text-slate-800">PDF:</strong> A finalized PDF with the signature is generated and stored.
            </li>
        </ul>
    </div>

    <!-- Client Interaction Portal -->
    <div class="border-t border-slate-200 pt-16 mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Client Interaction Portal</h2>
        <p class="text-slate-600 mb-6 font-medium">When you send an estimate, the client receives a secure link to a
            dedicated portal. This portal allows for real-time collaboration and feedback.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 border border-slate-200 rounded-lg shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Comments & Feedback</h3>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed mb-4">
                    Clients can click the comment icon next to any specific line item or use the general comments section at
                    the bottom.
                </p>
                <ul class="text-sm text-slate-600 space-y-2 list-disc list-inside">
                    <li>Ask clarifying questions about specific products.</li>
                    <li>Negotiate prices or quantities.</li>
                    <li>You receive real-time notifications for new comments.</li>
                </ul>
            </div>

            <div class="bg-white p-6 border border-slate-200 rounded-lg shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-rose-100 rounded-lg text-rose-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Request for Changes</h3>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed mb-4">
                    Instead of accepting, a client can choose to <strong>"Decline Estimate"</strong>. This triggers a
                    mandatory "Reason" field.
                </p>
                <ul class="text-sm text-slate-600 space-y-2 list-disc list-inside">
                    <li>Acts as a formal <strong>"Request for Changes"</strong> workflow.</li>
                    <li>The status updates to "Declined" in your dashboard.</li>
                    <li>You can review their reason (e.g., "Too expensive", "Change color") and create a new version.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Estimate Versioning -->
    <div class="border-t border-slate-200 pt-16">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Estimate Versioning</h2>
        <div class="flex flex-col md:flex-row gap-8 items-start">
            <div class="flex-1">
                <p class="text-slate-600 mb-6 font-medium">To maintain a clear history of negotiations, never overwrite a
                    sent estimate. Instead, create a new version.</p>

                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 shrink-0">
                            1</div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Create New Version</h4>
                            <p class="text-sm text-slate-600 mt-1">Open any specific estimate and look for the
                                <strong>"Create Version"</strong> button (often available after a decline or request for
                                changes).</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 shrink-0">
                            2</div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Automatic Numbering</h4>
                            <p class="text-sm text-slate-600 mt-1">The system automatically generates a version number
                                suffix (e.g., <code
                                    class="bg-slate-100 px-1 py-0.5 rounded text-xs font-mono">EST-1001-v2</code>). This
                                keeps the original <code
                                    class="bg-slate-100 px-1 py-0.5 rounded text-xs font-mono">EST-1001</code> intact for
                                record-keeping.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div
                            class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 shrink-0">
                            3</div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Version History</h4>
                            <p class="text-sm text-slate-600 mt-1">You can easily toggle between past versions to see what
                                changed or restore an older proposal if the client changes their mind.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/3 bg-slate-50 rounded-xl p-6 border border-slate-200">
                <h4 class="font-bold text-slate-900 mb-4 text-sm uppercase tracking-wide">Version Control Tips</h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3 text-sm text-slate-600">
                        <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Always create a new version for major price changes.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-slate-600">
                        <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Use comments to explain why a new version was created.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-slate-600">
                        <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Only the "Current Version" is viewable by the client link.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection