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
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .prose h4 {
            color: #334155;
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .prose p {
            margin-bottom: 1.5rem;
            line-height: 1.75;
        }

        .step-circle {
            min-width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background-color: #eff6ff;
            color: #2563eb;
            font-weight: 700;
            border: 2px solid #dbeafe;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-700 antialiased">

    <div x-data="{ mobileMenuOpen: false }">

        <!-- Mobile Header -->
        <header
            class="lg:hidden bg-white border-b border-gray-200 fixed top-0 w-full z-50 px-4 py-3 flex justify-between items-center shadow-sm">
            <span class="font-bold text-gray-900 text-lg">Docs</span>
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
        </header>

        <!-- Sidebar (Fixed on Desktop) -->
        <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-200 transition-transform duration-300 lg:translate-x-0 overflow-y-auto docs-sidebar transform shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
            <div class="p-6 sticky top-0 bg-white z-10 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 rounded-lg p-1.5 shadow-lg shadow-blue-600/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900 text-base leading-tight">Estimation Sys</h1>
                        <p class="text-xs text-gray-500 font-medium">User Manual v2.1</p>
                    </div>
                </div>
            </div>

            <nav class="px-4 py-8 space-y-8">
                <div>
                    <h5 class="px-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Getting Started</h5>
                    <div class="space-y-1">
                        <a href="{{ route('guide.show', 'getting-started') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*getting-started') || request()->routeIs('guide.index') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Introduction
                        </a>
                    </div>
                </div>

                <div>
                    <h5 class="px-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Core Features</h5>
                    <div class="space-y-1">
                        <a href="{{ route('guide.show', 'estimates') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*estimates') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Estimates & Sending
                        </a>
                        <a href="{{ route('guide.show', 'inventory') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*inventory') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Product Catalog
                        </a>
                    </div>
                </div>

                <div>
                    <h5 class="px-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Daily Operations</h5>
                    <div class="space-y-1">
                        <a href="{{ route('guide.show', 'approvals') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*approvals') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Inbox & Inbox
                        </a>
                        <a href="{{ route('guide.show', 'reports') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*reports') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Reports & Analytics
                        </a>
                        <a href="{{ route('guide.show', 'workflow') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*workflow') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Workflow & Logs
                        </a>
                    </div>
                </div>

                <div>
                    <h5 class="px-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">System</h5>
                    <div class="space-y-1">
                        <a href="{{ route('guide.show', 'admin') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*admin') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Admin Settings
                        </a>
                        <a href="{{ route('guide.show', 'api') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*api') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            Developer API
                        </a>
                        <a href="{{ route('guide.show', 'faq') }}"
                            class="flex items-center gap-2 px-2 py-2 text-sm font-medium rounded-md transition-all {{ request()->is('*faq') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            FAQ
                        </a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="lg:pl-72 flex-1 min-h-screen">
            <div class="max-w-5xl mx-auto px-6 py-12 lg:px-12 lg:py-16">
                @yield('content')
            </div>

            <footer class="border-t border-gray-200 mt-20 py-8 text-center text-sm text-gray-400">
                &copy; {{ date('Y') }} Custom Estimation App. All rights reserved.
            </footer>
        </main>

    </div>
</body>

</html>