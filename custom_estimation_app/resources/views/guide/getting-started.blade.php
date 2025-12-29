@extends('guide.layout')

@section('content')
    <!-- Hero -->
    <div class="mb-16 pb-12 border-b border-slate-100">
        <span class="text-blue-600 font-semibold tracking-wide text-sm uppercase">Getting Started</span>
        <h1 class="text-4xl font-extrabold text-slate-900 mt-2 mb-6">Welcome to the Estimation System</h1>
        <p class="text-lg text-slate-500 leading-relaxed mb-8">
            This detailed guide is designed to help you master every aspect of the application, from creating your first
            estimate to managing complex approval chains.
        </p>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100 transition hover:shadow-md">
                <h3 class="font-bold text-blue-900 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    New Estimators
                </h3>
                <p class="text-sm text-blue-700 mb-3">Jump straight to building proposals.</p>
                <a href="{{ route('guide.show', 'estimates') }}"
                    class="text-xs font-semibold text-blue-800 hover:underline">Go to Estimate Workflow &rarr;</a>
            </div>
            <div class="p-4 rounded-xl bg-purple-50 border border-purple-100 transition hover:shadow-md">
                <h3 class="font-bold text-purple-900 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Administrators
                </h3>
                <p class="text-sm text-purple-700 mb-3">Configure users and templates.</p>
                <a href="{{ route('guide.show', 'admin') }}"
                    class="text-xs font-semibold text-purple-800 hover:underline">Go to Admin Settings &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Authentication -->
    <div class="mb-16">
        <section class="prose max-w-none">
            <h2>Authentication & Security</h2>
            <p>Access to the system is strictly controlled. Ensure you are using your corporate email address.</p>

            <div class="not-prose mt-6 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                    <span class="font-medium text-slate-700 text-sm">Login Process</span>
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">Secure</span>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex gap-4">
                        <div class="step-circle shrink-0">1</div>
                        <div>
                            <h4 class="font-semibold text-slate-900">Navigate to Portal</h4>
                            <p class="text-sm text-slate-500 mt-1">Go to the homepage. If not logged in, you will see the
                                login screen.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="step-circle shrink-0">2</div>
                        <div>
                            <h4 class="font-semibold text-slate-900">Enter Credentials</h4>
                            <p class="text-sm text-slate-500 mt-1">Input your registered email and password. Use the "Forgot
                                Password" link if needed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Roles Table -->
    <div class="mb-16">
        <h2 class="text-2xl font-bold text-slate-900 mb-6">User Roles & Permissions</h2>
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-slate-300">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Role
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Key Capabilities
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Access Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                            <span
                                class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">Super
                                Admin</span>
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500">Full system control, manage users, edit settings.</td>
                        <td class="px-3 py-4 text-sm text-slate-500">Unrestricted</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                            <span
                                class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Estimator
                                Admin</span>
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500">Manage products, templates, and view all estimates.
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500">High</td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                            <span
                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Estimator</span>
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-500">Create regular estimates, view own data.</td>
                        <td class="px-3 py-4 text-sm text-slate-500">Standard</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection