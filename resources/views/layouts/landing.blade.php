<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ ($landingData->settings->siteName ?? '') . ' — ' . ($landingData->settings->siteTagline ?? '') }}</title>
    <meta name="description" content="{{ $landingData->settings->siteDescription ?? '' }}">
    <meta name="keywords" content="yoga, pilates, dance fitness, fitness studio, wellness">
    <meta name="author" content="{{ $landingData->settings->siteName ?? '' }}">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ ($landingData->settings->siteName ?? '') . ' — ' . ($landingData->settings->siteTagline ?? '') }}">
    <meta property="og:description" content="{{ $landingData->settings->siteDescription ?? '' }}">
    <meta property="og:image" content="{{ $landingData->settings->logoUrl ?? '' }}">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="{{ ($landingData->settings->siteName ?? '') . ' — ' . ($landingData->settings->siteTagline ?? '') }}">
    <meta property="twitter:description" content="{{ $landingData->settings->siteDescription ?? '' }}">
    <meta property="twitter:image" content="{{ $landingData->settings->logoUrl ?? '' }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        accent: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                        },
                        dark: {
                            900: '#0f172a',
                            800: '#1e293b',
                            700: '#334155',
                        }
                    }
                }
            }
        }
    </script>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/web/landing/landing.css') }}">
    @if(app()->getLocale() === 'ar')
    <style>
        body { direction: rtl; text-align: right; }
        .reveal { transform: translateY(24px); }
    </style>
    @endif
</head>
<body class="font-sans antialiased text-slate-800 bg-white dark:bg-dark-900 dark:text-slate-100 transition-colors duration-300">

    @include('landing.partials._header')

    <main>
        @yield('content')
    </main>

    @include('landing.partials._footer')

    <script src="{{ asset('js/web/landing/landing.js') }}"></script>
</body>
</html>
