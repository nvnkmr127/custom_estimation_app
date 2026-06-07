<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    API Developer Portal
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Retrieve authentication credentials and view API endpoint reference documentation.
                </p>
            </div>
            <div class="flex shrink-0">
                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                    API Version 1.0
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8" x-data="{ revealToken: false, copiedToken: false, copiedUrl: false }">
        <!-- Credentials Card -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-md">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                <h3 class="text-base font-semibold leading-6 text-slate-900">Developer Credentials</h3>
                <p class="mt-1 text-xs text-slate-500">Use these details to authenticate your requests against the API endpoints.</p>
            </div>
            <div class="p-6 space-y-6">
                <!-- Base URL -->
                <div>
                    <label class="block text-sm font-semibold text-slate-800">API Base URL</label>
                    <div class="mt-2 flex rounded-lg shadow-sm">
                        <div class="relative flex flex-grow items-stretch focus-within:z-10">
                            <input type="text" readonly value="{{ $apiUrl }}" 
                                class="block w-full rounded-none rounded-l-lg border-0 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-slate-50 font-mono">
                        </div>
                        <button type="button" @click="navigator.clipboard.writeText('{{ $apiUrl }}'); copiedUrl = true; setTimeout(() => copiedUrl = false, 2000)"
                            class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-lg px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                            <svg class="-ml-0.5 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-show="!copiedUrl">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0A2.25 2.25 0 0113.5 5.25h-3a2.25 2.25 0 01-2.166-1.638m7.332 0a2.25 2.25 0 00-2.35 1.89h-1.15a2.25 2.25 0 00-2.35-1.89m0 0a2.25 2.25 0 00-2.17 2.158v11.3c0 1.215.98 2.2 2.17 2.2h8.66c1.19 0 2.17-.985 2.17-2.2v-11.3a2.25 2.25 0 00-2.17-2.158" />
                            </svg>
                            <svg class="-ml-0.5 h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-show="copiedUrl" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="copiedUrl ? 'Copied!' : 'Copy'">Copy</span>
                        </button>
                    </div>
                </div>

                <!-- Session / Bearer Token -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-semibold text-slate-800">Active Bearer Token</label>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">
                            Valid for Current Session Only
                        </span>
                    </div>
                    <div class="mt-2 flex rounded-lg shadow-sm">
                        <div class="relative flex flex-grow items-stretch focus-within:z-10">
                            <input :type="revealToken ? 'text' : 'password'" readonly value="{{ $token }}" 
                                class="block w-full rounded-none rounded-l-lg border-0 py-2.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-slate-50 font-mono tracking-widest">
                        </div>
                        <button type="button" @click="revealToken = !revealToken"
                            class="relative -ml-px inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-show="!revealToken">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-show="revealToken" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                        <button type="button" @click="navigator.clipboard.writeText('{{ $token }}'); copiedToken = true; setTimeout(() => copiedToken = false, 2000)"
                            class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-lg px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                            <svg class="-ml-0.5 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-show="!copiedToken">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0A2.25 2.25 0 0113.5 5.25h-3a2.25 2.25 0 01-2.166-1.638m7.332 0a2.25 2.25 0 00-2.35 1.89h-1.15a2.25 2.25 0 00-2.35-1.89m0 0a2.25 2.25 0 00-2.17 2.158v11.3c0 1.215.98 2.2 2.17 2.2h8.66c1.19 0 2.17-.985 2.17-2.2v-11.3a2.25 2.25 0 00-2.17-2.158" />
                            </svg>
                            <svg class="-ml-0.5 h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-show="copiedToken" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="copiedToken ? 'Copied!' : 'Copy'">Copy</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Header Guidelines -->
                <div class="rounded-xl bg-indigo-50/50 p-4 border border-indigo-100 flex gap-3">
                    <svg class="h-6 w-6 text-indigo-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-indigo-950">Headers Authentication Requirement</h4>
                        <p class="mt-1 text-xs text-indigo-800 leading-relaxed">
                            Include the token in your requests via the standard <code class="bg-indigo-100 px-1 py-0.5 rounded text-indigo-900 font-mono">Authorization: Bearer [token_string]</code> header. You must also supply <code class="bg-indigo-100 px-1 py-0.5 rounded text-indigo-900 font-mono">Accept: application/json</code> on every API request.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation Card -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-slate-900">API Documentation</h3>
                    <p class="mt-1 text-xs text-slate-500">Interactive schema, request structures, and response samples.</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Smooth scroll sidebar helper links if wanted -->
                    <span class="text-xs text-slate-400">Rendered dynamically</span>
                </div>
            </div>
            
            <div class="p-6 md:p-8">
                <!-- Prose styled container for markdown -->
                <div id="markdown-doc-renderer" class="prose max-w-none prose-slate prose-indigo prose-headings:font-bold prose-a:text-indigo-600 hover:prose-a:text-indigo-500 prose-pre:bg-slate-900 prose-pre:text-slate-100 prose-pre:rounded-xl">
                    <div class="flex items-center justify-center py-12">
                        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-slate-500 font-medium">Parsing and rendering API reference manual...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- Include Marked.js from jsdelivr CDN safely for runtime markdown rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Read raw markdown string safely
                const rawMarkdown = {!! json_encode($markdown) !!};
                
                // Parse markdown into HTML
                const parsedHtml = marked.parse(rawMarkdown);
                
                // Set to content container
                const container = document.getElementById('markdown-doc-renderer');
                if (container) {
                    container.innerHTML = parsedHtml;
                    
                    // Add modern styling tweaks dynamically to tables and code snippets for premium aesthetics
                    container.querySelectorAll('table').forEach(table => {
                        table.classList.add('min-w-full', 'divide-y', 'divide-slate-200', 'border', 'border-slate-100', 'rounded-lg', 'my-6');
                        const headers = table.querySelectorAll('th');
                        headers.forEach(th => {
                            th.classList.add('bg-slate-50', 'px-4', 'py-3', 'text-left', 'text-xs', 'font-semibold', 'text-slate-700', 'uppercase', 'tracking-wider');
                        });
                        const cells = table.querySelectorAll('td');
                        cells.forEach(td => {
                            td.classList.add('px-4', 'py-3.5', 'text-sm', 'text-slate-600', 'border-t', 'border-slate-100');
                        });
                    });
                }
            } catch (err) {
                console.error('Failed to render markdown docs:', err);
                const container = document.getElementById('markdown-doc-renderer');
                if (container) {
                    container.innerHTML = `<div class="rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 text-sm font-medium">Failed to render documentation. Please verify file format.</div>`;
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
