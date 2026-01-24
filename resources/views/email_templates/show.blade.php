<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back & Header -->
        <div class="mb-8">
            <a href="{{ route('email-templates.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-6">
                <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Templates
            </a>

            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $emailTemplate->name }}</h1>
                    <div class="mt-2 flex items-center gap-3">
                        <span
                            class="inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1 text-xs font-mono font-medium text-gray-600 ring-1 ring-inset ring-gray-600/10">
                            {{ $emailTemplate->code }}
                        </span>
                    </div>
                </div>
                <div class="mt-6 sm:mt-0 flex gap-3">
                    <a href="{{ route('email-templates.edit', $emailTemplate) }}"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 hover:shadow-indigo-500/30 transition-all">
                        Edit Template
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <!-- Metadata Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-semibold text-gray-900">Template Details</h3>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Subject Line</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $emailTemplate->subject }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Last Updated</dt>
                        <dd class="text-base font-medium text-gray-900">
                            {{ $emailTemplate->updated_at->format('M d, Y h:i A') }}</dd>
                    </div>
                    @if($emailTemplate->description)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 mb-1">Description</dt>
                            <dd class="text-sm text-gray-700 leading-relaxed">{{ $emailTemplate->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Content Preview -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Content Preview</h3>
                    <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">HTML View</span>
                </div>
                <div class="p-8 bg-white">
                    <div class="prose prose-indigo max-w-none p-6 border border-gray-200 rounded-lg bg-gray-50">
                        {!! $emailTemplate->body_html !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>