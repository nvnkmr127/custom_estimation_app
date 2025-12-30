<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }
    </style>
</head>

<body class="h-full flex items-center justify-center p-6 text-slate-900 antialiased">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <h1 class="text-9xl font-bold text-indigo-600 opacity-20">@yield('code')</h1>
        </div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">@yield('title')</h2>
        <p class="mt-4 text-lg text-slate-600">@yield('message')</p>
        <div class="mt-10 flex items-center justify-center gap-x-6">
            <a href="{{ url('/') }}"
                class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                Back to Dashboard
            </a>
            <a href="mailto:support@example.com"
                class="text-sm font-semibold text-slate-900 border-b border-slate-300 hover:border-slate-900 transition-all">
                Contact Support <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</body>

</html>