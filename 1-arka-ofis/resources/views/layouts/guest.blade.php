<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Giriş') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-slate-200 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-black text-white shadow-lg">
                    ÖM
                </div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ön Muhasebe &amp; Stok Takip Sistemi</p>
            </div>

            @include('partials.flash')

            <main class="card p-6 sm:p-8">
                @yield('content')
            </main>

            <p class="mt-6 text-center text-xs text-slate-400">Staj Projesi &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
