{{-- /home/lenovo/work/projects/pilates/resources/views/layouts/landing.blade.php --}}
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
                            50: 'var(--primary-50)',
                            100: 'var(--primary-100)',
                            200: 'var(--primary-200)',
                            300: 'var(--primary-300)',
                            400: 'var(--primary-400)',
                            500: 'var(--primary-500)',
                            600: 'var(--primary-600)',
                            700: 'var(--primary-700)',
                            800: 'var(--primary-800)',
                            900: 'var(--primary-900)',
                        },
                        accent: {
                            50: 'var(--accent-50)',
                            100: 'var(--accent-100)',
                            200: 'var(--accent-200)',
                            300: 'var(--accent-300)',
                            400: 'var(--accent-400)',
                            500: 'var(--accent-500)',
                            600: 'var(--accent-600)',
                            700: 'var(--accent-700)',
                            800: 'var(--accent-800)',
                            900: 'var(--accent-900)',
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

    @php
        $s = $landingData->settings;
        $primary = $s->brandPrimaryColor ?? '#262D35';
        $secondary = $s->brandSecondaryColor ?? '#F3EFE3';
        $accent = $s->brandAccentColor ?? '#B8A18B';
        $p = sscanf($primary, '#%02x%02x%02x');
        $a = sscanf($accent, '#%02x%02x%02x');
    @endphp
    <style>
        :root {
            --brand-primary: {{ $primary }};
            --brand-primary-rgb: {{ implode(', ', $p) }};
            --brand-secondary: {{ $secondary }};
            --brand-accent: {{ $accent }};
            --brand-accent-rgb: {{ implode(', ', $a) }};

            --primary-50:  color-mix(in srgb, var(--brand-primary) 5%, white);
            --primary-100: color-mix(in srgb, var(--brand-primary) 10%, white);
            --primary-200: color-mix(in srgb, var(--brand-primary) 30%, white);
            --primary-300: color-mix(in srgb, var(--brand-primary) 50%, white);
            --primary-400: color-mix(in srgb, var(--brand-primary) 70%, white);
            --primary-500: var(--brand-primary);
            --primary-600: color-mix(in srgb, var(--brand-primary) 80%, black);
            --primary-700: color-mix(in srgb, var(--brand-primary) 70%, black);
            --primary-800: color-mix(in srgb, var(--brand-primary) 60%, black);
            --primary-900: color-mix(in srgb, var(--brand-primary) 50%, black);

            --accent-50:  color-mix(in srgb, var(--brand-accent) 5%, white);
            --accent-100: color-mix(in srgb, var(--brand-accent) 10%, white);
            --accent-200: color-mix(in srgb, var(--brand-accent) 30%, white);
            --accent-300: color-mix(in srgb, var(--brand-accent) 50%, white);
            --accent-400: color-mix(in srgb, var(--brand-accent) 70%, white);
            --accent-500: var(--brand-accent);
            --accent-600: color-mix(in srgb, var(--brand-accent) 80%, black);
            --accent-700: color-mix(in srgb, var(--brand-accent) 70%, black);
            --accent-800: color-mix(in srgb, var(--brand-accent) 60%, black);
            --accent-900: color-mix(in srgb, var(--brand-accent) 50%, black);
        }
    </style>

    <script src="https://unpkg.com/lucide@0.344.0/dist/umd/lucide.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/web/landing/landing.css') }}">
    @if(app()->getLocale() === 'ar')
    <style>
        body { direction: rtl; text-align: right; }
        .reveal { transform: translateY(24px); }
    </style>
    @endif
</head>
<body class="flex flex-col min-h-screen font-sans antialiased text-slate-800 bg-[var(--brand-secondary)] dark:bg-dark-900 dark:text-slate-100 transition-colors duration-300">

    @include('landing.partials._header')

    <main class="flex-grow">
        @yield('content')
    </main>

    @include('landing.partials._footer')

    <script src="{{ asset('js/web/landing/landing.js') }}"></script>
</body>
</html>
