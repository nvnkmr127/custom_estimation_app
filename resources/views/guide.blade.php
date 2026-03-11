<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enterprise User Guide | Estimation System</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .docs-sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .docs-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .docs-sidebar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 2px;
        }

        .prose h3 {
            color: #1e293b;
            margin-top: 2rem;
            font-weight: 600;
        }

        .prose h4 {
            color: #334155;
            margin-top: 1.5rem;
            font-weight: 600;
        }

        .step-circle {
            min-width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background-color: #eff6ff;
            color: #2563eb;
            font-weight: 600;
            border: 2px solid #dbeafe;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-600 antialiased">

    <div x-data="{ mobileMenuOpen: false, search: '' }">

        <!-- Mobile Header -->
        <header
            class="md:hidden bg-white border-b border-slate-200 fixed top-0 w-full z-50 px-4 py-3 flex justify-between items-center">
            <span class="font-bold text-slate-800 text-lg">Docs</span>
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
        </header>

        <div class="flex min-h-screen pt-14 md:pt-0">

            <!-- Sidebar -->
            <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-slate-200 transition-transform duration-300 md:translate-x-0 md:static md:h-screen md:sticky md:top-0 overflow-y-auto docs-sidebar transform">
                <div class="p-6 border-b border-slate-100">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="bg-blue-600 rounded p-1">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h1 class="font-bold text-slate-900 text-lg">Estimation Docs</h1>
                    </div>
                    <p class="text-xs text-slate-400">Employee Training Manual v2.1</p>
                </div>

                <div class="px-3 py-4">
                    <input type="text" x-model="search" placeholder="Search documentation..."
                        class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-blue-500 mb-4 transition-colors">

                    <nav class="space-y-8">
                        <div>
                            <h5 class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Getting
                                Started</h5>
                            <a href="#quick-start"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">System
                                Overview</a>
                            <a href="#authentication"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">Login
                                & Security</a>
                            <a href="#roles"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">User
                                Roles</a>
                        </div>

                        <div>
                            <h5 class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Core
                                Workflow</h5>
                            <a href="#estimates-create"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">Creating
                                Estimates</a>
                            <a href="#products-manage"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">Product
                                Library</a>
                            <a href="#approvals"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">Approval
                                Chains</a>
                        </div>

                        <div>
                            <h5 class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Advanced
                            </h5>
                            <a href="#admin-settings"
                                class="block px-3 py-2 text-sm text-slate-600 rounded-md hover:bg-slate-50 hover:text-blue-600 transition-colors">Admin
                                Settings</a>
                        </div>
                    </nav>
                </div>
            </aside>

            <!-- Content -->
            <main class="flex-1 min-w-0 bg-white">
                <div class="max-w-4xl mx-auto px-6 py-12 lg:px-12">

                    <!-- Hero -->
                    <div id="quick-start" class="mb-16 pb-12 border-b border-slate-100">
                        <span class="text-blue-600 font-semibold tracking-wide text-sm uppercase">Documentation</span>
                        <h1 class="text-4xl font-extrabold text-slate-900 mt-2 mb-6">Mastering the Estimation System
                        </h1>
                        <p class="text-lg text-slate-500 leading-relaxed mb-8">
                            Welcome to your comprehensive guide for the Custom Estimation Application. This platform is
                            designed to streamline your workflow from initial lead to signed contract.
                        </p>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                                <h3 class="font-bold text-blue-900 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    For Sales Team
                                </h3>
                                <p class="text-sm text-blue-700">Learn how to quickly generate accurate estimates, use
                                    room templates, and close deals with digital signatures.</p>
                            </div>
                            <div class="p-4 rounded-xl bg-purple-50 border border-purple-100">
                                <h3 class="font-bold text-purple-900 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    For Admins
                                </h3>
                                <p class="text-sm text-purple-700">Configure global settings, manage the product
                                    catalog, set up approval chains, and monitor analytics.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Authentication -->
                    <div id="authentication" class="mb-16 scroll-mt-24">
                        <section class="prose max-w-none">
                            <h2>Authentication & Security</h2>
                            <p>Access to the system is strictly controlled. Ensure you are using your corporate email
                                address.</p>

                            <div
                                class="not-prose mt-6 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                                <div
                                    class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                                    <span class="font-medium text-slate-700 text-sm">Login Process</span>
                                    <span
                                        class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">Secure</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="flex gap-4">
                                        <div class="step-circle shrink-0">1</div>
                                        <div>
                                            <h4 class="font-semibold text-slate-900">Navigate to Portal</h4>
                                            <p class="text-sm text-slate-500 mt-1">Go to the homepage. If not logged in,
                                                you will see the login screen.</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <div class="step-circle shrink-0">2</div>
                                        <div>
                                            <h4 class="font-semibold text-slate-900">Enter Credentials</h4>
                                            <p class="text-sm text-slate-500 mt-1">Input your registered email and
                                                password. Use the "Forgot Password" link if needed.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Roles Table -->
                    <div id="roles" class="mb-16 scroll-mt-24">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6">User Roles & Permissions</h2>
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-slate-300">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">
                                            Role</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Key
                                            Capabilities</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Access
                                            Level</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr>
                                        <td
                                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                            <span
                                                class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">Super
                                                Admin</span>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-slate-500">Full system control, manage users,
                                            edit settings.</td>
                                        <td class="px-3 py-4 text-sm text-slate-500">Unrestricted</td>
                                    </tr>
                                    <tr>
                                        <td
                                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                            <span
                                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Estimator
                                                Admin</span>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-slate-500">Manage products, templates, and
                                            view all estimates.</td>
                                        <td class="px-3 py-4 text-sm text-slate-500">High</td>
                                    </tr>
                                    <tr>
                                        <td
                                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                            <span
                                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Estimator</span>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-slate-500">Create regular estimates, view own
                                            data.</td>
                                        <td class="px-3 py-4 text-sm text-slate-500">Standard</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Creating Estimates Deep Dive -->
                    <div id="estimates-create" class="mb-16 scroll-mt-24">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6">Creating Estimates: The Workflow</h2>
                        <p class="text-slate-600 mb-8">This is the core function of the application. Follow this
                            standard operating procedure to create high-quality proposals.</p>

                        <!-- Use Case Card -->
                        <div
                            class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 text-white shadow-xl mb-10 relative overflow-hidden">
                            <div
                                class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl">
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-2 mb-4">
                                    <span
                                        class="bg-blue-500 text-white text-xs font-bold px-2 py-1 rounded uppercase tracking-wide">Use
                                        Case</span>
                                    <span class="text-slate-300 text-sm">Residential Project</span>
                                </div>
                                <h3 class="text-2xl font-bold mb-2 text-white mt-0">Scenario: Kitchen Remodel ($15,000)
                                </h3>
                                <p class="text-slate-300 text-sm mb-6 max-w-xl">
                                    Client "John Doe" wants a full kitchen renovation. You need to create an estimate
                                    with cabinetry, countertops, and labor, then send it for digital signature.
                                </p>
                                <div class="flex flex-wrap gap-2 text-xs font-mono text-blue-200">
                                    <span class="bg-white/10 px-2 py-1 rounded">Step 1: Select "Kitchen Template"</span>
                                    <span class="bg-white/10 px-2 py-1 rounded">Step 2: Add "Granite Countertop"</span>
                                    <span class="bg-white/10 px-2 py-1 rounded">Step 3: AI Generate Description</span>
                                    <span class="bg-white/10 px-2 py-1 rounded">Step 4: Send via Email</span>
                                </div>
                            </div>
                        </div>

                        <!-- Steps -->
                        <div class="space-y-12">
                            <div class="flex gap-6">
                                <div class="flex flex-col items-center">
                                    <div
                                        class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-lg shadow-blue-200">
                                        1</div>
                                    <div class="w-0.5 h-full bg-slate-200 my-2"></div>
                                </div>
                                <div class="pb-8">
                                    <h4 class="text-xl font-bold text-slate-900 mb-2">Initiate & Client Selection</h4>
                                    <p class="text-slate-600 mb-3">Click <span class="font-semibold text-slate-900">New
                                            Estimate</span>. In the client dropdown, search for the client. If
                                        this searches the client database live.</p>
                                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                        <p class="text-sm text-yellow-700"><strong>Tip:</strong> If the client doesn't
                                            exist, you must create them in the CRM first or use the "Quick Add" feature
                                            if enabled.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-6">
                                <div class="flex flex-col items-center">
                                    <div
                                        class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-lg shadow-blue-200">
                                        2</div>
                                    <div class="w-0.5 h-full bg-slate-200 my-2"></div>
                                </div>
                                <div class="pb-8">
                                    <h4 class="text-xl font-bold text-slate-900 mb-2">Add Line Items</h4>
                                    <p class="text-slate-600 mb-3">You can add items manually or use the <strong>Product
                                            Picker</strong>. </p>
                                    <ul class="list-disc pl-5 space-y-1 text-slate-600 mb-3">
                                        <li><strong>Room Templates:</strong> Pre-load a set of items (e.g., "Standard
                                            Bathroom") to save time.</li>
                                        <li><strong>Search:</strong> Type "Sink" to see all sink options with images and
                                            prices.</li>
                                        <li><strong>AI Assist:</strong> Click the "Sparkles" icon next to a description
                                            to have AI write a professional product description for you.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex gap-6">
                                <div class="flex flex-col items-center">
                                    <div
                                        class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold shadow-lg shadow-blue-200">
                                        3</div>
                                    <div class="w-0.5 bg-transparent my-2"></div> <!-- End of line -->
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-slate-900 mb-2">Review & Send</h4>
                                    <p class="text-slate-600 mb-3">Check the totals. If the total exceeds your approval
                                        limit, the "Send" button will change to "Request Approval".</p>
                                    <p class="text-slate-600">Once approved (or if within limits), clicking
                                        <strong>Send</strong> emails a secure link to the client. They can view the PDF
                                        and sign with their finger/mouse.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Management -->
                    <div id="products-manage" class="mb-16 scroll-mt-24">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-slate-900">Product Library & Inventory</h2>
                            <span class="bg-slate-100 text-slate-600 text-xs px-2 py-1 rounded">Admin Only</span>
                        </div>
                        <p class="text-slate-600 mb-6">Maintain an accurate database of materials and labor costs to
                            ensure consistent pricing.</p>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div
                                class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                                <h3 class="font-bold text-slate-800 mb-2 text-lg">Single Entry</h3>
                                <p class="text-sm text-slate-500 mb-4">Best for adding one-off new items.</p>
                                <ol class="list-decimal pl-4 text-sm text-slate-600 space-y-2">
                                    <li>Go to <strong>Products > Add New</strong>.</li>
                                    <li>Enter SKU (must be unique).</li>
                                    <li>Upload a high-quality product image.</li>
                                    <li>Set base price and unit (sq ft, each, etc.).</li>
                                </ol>
                            </div>
                            <div
                                class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                                <h3 class="font-bold text-slate-800 mb-2 text-lg">Bulk Import</h3>
                                <p class="text-sm text-slate-500 mb-4">Best for quarterly price updates.</p>
                                <ol class="list-decimal pl-4 text-sm text-slate-600 space-y-2">
                                    <li>Download the CSV template.</li>
                                    <li>Fill in rows (do not change headers).</li>
                                    <li>Upload via <strong>Products > Import</strong>.</li>
                                    <li>Review the "Success/Fail" report.</li>
                                </ol>
                            </div>
                        </div>
                    </div>


                    <!-- Troubleshooting / Footer -->
                    <div class="border-t border-slate-200 pt-12 mt-12 pb-24">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Need Help?</h3>
                        <p class="text-slate-600 mb-4">If you encounter issues not covered in this guide, please contact
                            support.</p>
                        <div class="flex gap-4">
                            <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Contact IT Support
                                &rarr;</a>
                            <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Report a Bug &rarr;</a>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>

</html>