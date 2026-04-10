<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white font-sans text-slate-900 antialiased selection:bg-indigo-500 selection:text-white">

    <div class="relative min-h-screen overflow-hidden isolate">
        <!-- Decoration -->
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
            aria-hidden="true">
            <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#818cf8] to-[#4f46e5] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)">
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-6 pb-24 pt-10 sm:pb-32 lg:flex lg:px-8 lg:py-40">
            <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-xl lg:flex-shrink-0 lg:pt-8">
                <!-- Logo -->
                <div class="mt-24 sm:mt-32 lg:mt-16">
                    <a href="#" class="inline-flex items-center space-x-2">
                        @if(isset($app_settings['app_logo']) && $app_settings['app_logo'])
                            <img src="{{ asset($app_settings['app_logo']) }}" alt="{{ config('app.name') }}"
                                class="h-10 w-auto rounded-lg shadow-lg shadow-indigo-500/30">
                        @else
                            <span class="rounded-lg bg-indigo-600 p-2 text-white shadow-lg shadow-indigo-500/30">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                                </svg>
                            </span>
                        @endif
                        <span class="text-xl font-bold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                    </a>
                </div>

                <h1 class="mt-10 text-4xl font-bold tracking-tight text-slate-900 sm:text-6xl">
                    Estimates made simple.
                </h1>
                <p class="mt-6 text-lg leading-8 text-slate-600">
                    Create, manage, and track professional estimates for your clients with a platform designed for speed
                    and accuracy.
                </p>

                <div class="mt-10 flex items-center gap-x-6">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all">
                                Create account
                            </a>
                        @endif
                        <a href="#"
                            class="text-sm font-semibold leading-6 text-slate-900 hover:text-indigo-600 transition-colors">
                            Learn more <span aria-hidden="true">→</span>
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Hero Image / Visual -->
            <div
                class="mx-auto mt-16 flex max-w-2xl sm:mt-24 lg:ml-10 lg:mt-0 lg:mr-0 lg:max-w-none lg:flex-none xl:ml-32">
                <div class="max-w-3xl flex-none sm:max-w-5xl lg:max-w-none">
                    <div
                        class="-m-2 rounded-xl bg-slate-900/5 p-2 ring-1 ring-inset ring-slate-900/10 lg:-m-4 lg:rounded-2xl lg:p-4">
                        <div class="w-[60rem] rounded-md bg-white shadow-2xl ring-1 ring-slate-900/10 overflow-hidden">
                            <!-- Placeholder for dashboard preview -->
                            <div class="bg-slate-50 border-b border-slate-100 p-4 flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="p-8 grid gap-6">
                                <div class="h-8 w-1/3 bg-slate-100 rounded mb-4"></div>
                                <div class="space-y-3">
                                    <div class="h-4 w-full bg-slate-50 rounded"></div>
                                    <div class="h-4 w-5/6 bg-slate-50 rounded"></div>
                                    <div class="h-4 w-4/6 bg-slate-50 rounded"></div>
                                </div>
                                <div class="grid grid-cols-3 gap-4 mt-4">
                                    <div class="h-24 bg-indigo-50 rounded-lg border border-indigo-100"></div>
                                    <div class="h-24 bg-slate-50 rounded-lg border border-slate-100"></div>
                                    <div class="h-24 bg-slate-50 rounded-lg border border-slate-100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
