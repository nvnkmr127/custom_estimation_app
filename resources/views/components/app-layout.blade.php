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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @if(isset($app_settings['app_favicon']))
        <link rel="icon" type="image/x-icon" href="{{ $app_settings['app_favicon'] }}">
    @endif

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="h-full bg-slate-50 font-sans antialiased text-slate-900">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        Skip to main content
    </a>

    <x-global-search />
    <x-toast />
    <livewire:notification-overlay />
    <livewire:notification-poller />
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
                @if(isset($app_settings['app_logo']))
                    <img src="{{ $app_settings['app_logo'] }}" alt="{{ $app_settings['app_name'] ?? config('app.name') }}"
                        class="h-8 w-auto">
                @else
                    <h1 class="text-xl font-bold tracking-tight">
                        {{ $app_settings['app_name'] ?? config('app.name', 'Estimation App') }}
                    </h1>
                @endif
            </div>

            <!-- Navigation -->
            <nav class="flex flex-1 flex-col overflow-y-auto px-6 pb-4 pt-5">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    @foreach($sidebarMenu as $group)
                        <li>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">{{ $group['group'] }}</div>
                            <ul role="list" class="-mx-2 space-y-1">
                                @foreach($group['items'] as $item)
                                    <li>
                                        @if (!isset($item['role']) || Auth::user()->hasRole($item['role']))
                                        <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                                            @if(isset($item['target'])) target="{{ $item['target'] }}" @endif
                                            class="{{ $item['active'] ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }} group flex gap-x-3 rounded-lg p-2 text-sm leading-6 font-medium transition-all">
                                            <x-dynamic-component :component="$item['icon']" 
                                                class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
                                            <span class="truncate">{{ $item['label'] }}</span>
                                            
                                            @if(isset($item['badge']) && $item['badge'] > 0)
                                                @php
                                                    $badgeColor = $item['badge_color'] ?? 'indigo';
                                                    $colorClasses = [
                                                        'indigo' => 'bg-indigo-500 ring-indigo-400',
                                                        'amber' => 'bg-amber-500 ring-amber-400',
                                                        'rose' => 'bg-rose-500 ring-rose-400',
                                                    ];
                                                    $colorClass = $colorClasses[$badgeColor] ?? $colorClasses['indigo'];
                                                @endphp
                                                <span class="ml-auto w-6 min-w-max whitespace-nowrap rounded-full {{ $colorClass }} px-2.5 py-0.5 text-center text-[10px] font-bold text-white ring-1 ring-inset">{{ $item['badge'] }}</span>
                                            @endif
                                        </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
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

                    @if (session('error'))
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
                                    <p class="text-sm font-semibold text-rose-800">{{ session('error') }}</p>
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
