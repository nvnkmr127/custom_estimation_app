<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back Button & Header -->
        <div class="mb-8">
            <a href="{{ route('email-templates.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-6">
                <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Templates
            </a>

            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Create New Email Template</h1>
            <p class="mt-2 text-sm text-gray-500">
                Design a new reusable email layout for your estimates and notifications.
            </p>
        </div>

        <form action="{{ route('email-templates.store') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Basic Info Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-8 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-900 mb-2">Template Name</label>
                            <input type="text" name="name" id="name" required
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5"
                                placeholder="e.g. Client Welcome Email">
                        </div>

                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-900 mb-2">
                                Unique Code <span class="text-gray-400 font-normal">(System Identifier)</span>
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">emails.</span>
                                </div>
                                <input type="text" name="code" id="code" required
                                    class="block w-full rounded-lg border-gray-300 pl-16 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5"
                                    placeholder="welcome">
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Used by the system to trigger this specific email.</p>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-900 mb-2">Subject
                                Line</label>
                            <input type="text" name="subject" id="subject" required
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5"
                                placeholder="e.g. Welcome to Our Service!">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-900 mb-2">Description
                                <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <input type="text" name="description" id="description"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5"
                                placeholder="What is this email used for?">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editor Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">HTML Content</h3>
                    <div class="text-xs text-indigo-600 bg-indigo-50 px-2 py-1 rounded font-medium">
                        Tips: Use Blade syntax like @{{ $variable }}
                    </div>
                </div>
                <div class="p-0">
                    <textarea name="body_html" id="body_html" rows="20"
                        class="block w-full border-0 py-4 px-6 text-gray-900 shadow-none ring-0 placeholder:text-gray-400 focus:ring-0 sm:text-sm font-mono sm:leading-6 bg-gray-50/10"
                        placeholder="<html>
    <body>
        <h1>Hello, @{{ $name }}!</h1>
        <p>This is your custom template.</p>
    </body>
</html>" required></textarea>
                </div>
                <!-- Quick Variables Reference (Static for now, but helpful) -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Common Variables</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center rounded-md bg-white border border-gray-200 px-2 py-1 text-xs font-mono font-medium text-gray-600">@{{
                            $client_name }}</span>
                        <span
                            class="inline-flex items-center rounded-md bg-white border border-gray-200 px-2 py-1 text-xs font-mono font-medium text-gray-600">@{{
                            $estimate_number }}</span>
                        <span
                            class="inline-flex items-center rounded-md bg-white border border-gray-200 px-2 py-1 text-xs font-mono font-medium text-gray-600">@{{
                            $total_amount }}</span>
                        <span
                            class="inline-flex items-center rounded-md bg-white border border-gray-200 px-2 py-1 text-xs font-mono font-medium text-gray-600">@{{
                            $company_name }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('email-templates.index') }}"
                    class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit"
                    class="rounded-full bg-indigo-600 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-500 hover:shadow-indigo-500/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all duration-300">
                    Create Template
                </button>
            </div>
        </form>
    </div>
</x-app-layout>