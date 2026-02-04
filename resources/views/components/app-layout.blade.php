<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-50 font-sans antialiased text-slate-900">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        Skip to main content
    </a>

    <x-global-search />
    <div x-data="{ sidebarOpen: false }" class="min-h-full">

        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/80 z-40 lg:hidden"
            @click="sidebarOpen = false" style="display: none;"></div>

        <!-- Sidebar -->
        <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 z-50 flex w-72 flex-col bg-slate-900 transition-transform duration-300 ease-in-out lg:translate-x-0 text-white">

            <!-- Logo area -->
            <div class="flex h-16 shrink-0 items-center px-6 bg-slate-950/50">
                <h1 class="text-xl font-bold tracking-tight">Estimation App</h1>
            </div>

            <!-- Navigation -->
            <nav class="flex flex-1 flex-col overflow-y-auto px-6 pb-4 pt-5">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <!-- Overview Section -->
                    <li>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Overview</div>
                        <ul role="list" class="-mx-2 space-y-1">
                            <li>
                                <a href="{{ route('dashboard') }}"
                                    class="{{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('notifications.index') }}"
                                    class="{{ request()->routeIs('notifications.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    Notifications
                                    {{-- $unreadCount passed via composer --}}
                                    @if($unreadCount > 0)
                                        <span
                                            class="ml-auto w-6 min-w-max whitespace-nowrap rounded-full bg-indigo-600 px-2.5 py-0.5 text-center text-[10px] font-bold text-white ring-1 ring-inset ring-indigo-500">{{ $unreadCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('approvals.index') }}"
                                    class="{{ request()->routeIs('approvals.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                    </svg>
                                    Approvals
                                    {{-- $pendingApprovals passed via composer --}}
                                    @if($pendingApprovals > 0)
                                        <span
                                            class="ml-auto w-6 min-w-max whitespace-nowrap rounded-full bg-amber-500 px-2.5 py-0.5 text-center text-[10px] font-bold text-white ring-1 ring-inset ring-amber-400">{{ $pendingApprovals }}</span>
                                    @endif
                                </a>
                            </li>
                            @if(auth()->user()->isAdmin())
                                <li>
                                    <a href="{{ route('activities.index') }}"
                                        class="{{ request()->routeIs('activities.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Activity Log
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>

                    <!-- Operations Section -->
                    <li>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Operations</div>
                        <ul role="list" class="-mx-2 space-y-1">
                            <li>
                                <a href="{{ route('estimates.index') }}"
                                    class="{{ request()->routeIs('estimates.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    Estimates
                                </a>
                            </li>
                            @if(auth()->user()->isAdmin())
                                <li>
                                    <a href="{{ route('clients.index') }}"
                                        class="{{ request()->routeIs('clients.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                        Clients
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('tasks.index') }}"
                                        class="{{ request()->routeIs('tasks.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Tasks
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('reminders.index') }}"
                                        class="{{ request()->routeIs('reminders.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        Reminders
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a href="{{ route('reports.index') }}"
                                    class="{{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                    Reports
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Catalog Section -->
                    @if(auth()->user()->isAdmin())
                        <li>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Catalog</div>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <a href="{{ route('brands.index') }}"
                                        class="{{ request()->routeIs('brands.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                        </svg>
                                        Brands
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('products.index') }}"
                                        class="{{ request()->routeIs('products.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                        </svg>
                                        Products
                                        {{-- $pendingSuggestions passed via composer --}}
                                        @if($pendingSuggestions > 0)
                                            <span
                                                class="ml-auto w-6 min-w-max whitespace-nowrap rounded-full bg-amber-500 px-2.5 py-0.5 text-center text-[10px] font-bold text-white ring-1 ring-inset ring-amber-400">{{ $pendingSuggestions }}</span>
                                        @endif

                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('categories.index') }}"
                                        class="{{ request()->routeIs('categories.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                        </svg>
                                        Categories
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('templates.index') }}"
                                        class="{{ request()->routeIs('templates.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                        Room Templates
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('pdf-templates.index') }}"
                                        class="{{ request()->routeIs('pdf-templates.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                        PDF Templates
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('email-templates.index') }}"
                                        class="{{ request()->routeIs('email-templates.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                        </svg>
                                        Email Templates
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('packages.index') }}"
                                        class="{{ request()->routeIs('packages.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                        </svg>
                                        Packages
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- Administration Section -->
                    @if(auth()->user()->isAdmin())
                        <li>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Users & Access
                            </div>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <a href="{{ route('users.index') }}"
                                        class="{{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                        User Directory
                                    </a>
                                </li>
                                @if(auth()->user()->hasRole('super_admin'))
                                    <li>
                                        <a href="{{ route('permissions.index') }}"
                                            class="{{ request()->routeIs('permissions.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                            </svg>
                                            Capability Matrix
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a href="{{ route('approval-chains.index') }}"
                                        class="{{ request()->routeIs('approval-chains.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                        </svg>
                                        Approval Flows
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Audits &
                                Webhooks</div>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <a href="{{ route('event-logs.index') }}"
                                        class="{{ request()->routeIs('event-logs.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        System Events
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.webhooks.catchers.index') }}"
                                        class="{{ request()->routeIs('admin.webhooks.catchers.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Inbound Catchers
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.webhooks.index') }}"
                                        class="{{ request()->routeIs('admin.webhooks.*') && !request()->routeIs('admin.webhooks.events.*') && !request()->routeIs('admin.webhooks.logs.*') && !request()->routeIs('admin.webhooks.dlq.*') && !request()->routeIs('admin.webhooks.catchers.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                        </svg>
                                        Outbound Endpoints
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.webhooks.events.index') }}"
                                        class="{{ request()->routeIs('admin.webhooks.events.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                                        </svg>
                                        Payload Explorer
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.webhooks.logs.index') }}"
                                        class="{{ request()->routeIs('admin.webhooks.logs.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                        Delivery Logs
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.webhooks.dlq.index') }}"
                                        class="{{ request()->routeIs('admin.webhooks.dlq.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        Dead Letter Queue
                                        @if($dlqCount > 0)
                                            <span
                                                class="ml-auto w-6 min-w-max whitespace-nowrap rounded-full bg-rose-500 px-2.5 py-0.5 text-center text-[10px] font-bold text-white ring-1 ring-inset ring-rose-400">{{ $dlqCount }}</span>
                                        @endif
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">System Logic
                            </div>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <a href="{{ route('automation.index') }}"
                                        class="{{ request()->routeIs('automation.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                        </svg>
                                        Automation Engine
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.nurture') }}"
                                        class="{{ request()->routeIs('settings.nurture') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                        </svg>
                                        Nurture Rules
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.edit') }}"
                                        class="{{ request()->routeIs('settings.edit') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                                        </svg>
                                        Global Settings
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- Support Section -->
                    <li>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Support</div>
                        <ul role="list" class="-mx-2 space-y-1">
                            <li>
                                <a href="{{ route('guide.index') }}" target="_blank"
                                    class="{{ request()->routeIs('guide.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    </svg>
                                    User Guide
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>

            <!-- User Profile (Bottom Sidebar) -->
            <div class="flex items-center gap-x-4 px-6 py-6 border-t border-slate-800">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-x-4 flex-1 min-w-0 group">
                    @if(Auth::user()->avatar)
                        <img class="h-9 w-9 rounded-full bg-slate-800 object-cover" src="{{ Auth::user()->avatar }}" alt="">
                    @else
                        <div
                            class="h-9 w-9 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold uppercase group-hover:bg-indigo-500 transition-colors">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <span
                            class="block truncate text-sm font-semibold text-white group-hover:text-indigo-400 transition-colors">{{ Auth::user()->name }}</span>
                        <span
                            class="block truncate text-xs text-slate-400 group-hover:text-slate-300 transition-colors">{{ Auth::user()->email }}</span>
                    </div>
                </a>

                <!-- Logout Form -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-white" title="Log out">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:pl-72 flex flex-col min-h-screen">
            <!-- Top Mobile Bar -->
            <div
                class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 bg-white/80 backdrop-blur-md px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 lg:hidden">
                <button type="button" class="-m-2.5 p-2.5 text-slate-700 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="text-sm font-semibold leading-6 text-slate-800">{{ config('app.name') }}</div>
            </div>

            @if (isset($header))
                <header class="bg-white border-b border-slate-200">
                    <div class="max-w-screen-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main id="main-content" class="flex-1 py-8 sm:py-10">
                <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div
                            class="rounded-xl bg-emerald-50 p-4 mb-6 border border-emerald-200 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div
                            class="rounded-xl bg-rose-50 p-4 mb-6 border border-rose-200 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-rose-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-semibold text-rose-800">There were {{ $errors->count() }} errors
                                        with your submission</h3>
                                    <div class="mt-2 text-sm text-rose-700">
                                        <ul role="list" class="list-disc pl-5 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
    @livewireScripts
</body>

</html>