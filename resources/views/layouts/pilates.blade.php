<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Inter', sans-serif;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
        }
    </style>
</head>

<body class="antialiased">
    <header class="bg-white shadow-sm border-b border-emerald-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-serif text-emerald-800 tracking-wide font-bold">
                {{ config('app.name', 'Pilates Studio') }}
            </a>

            <div class="flex items-center gap-6">
                <x-language-switcher />
            </div>
        </div>
    </header>

    <main class="min-h-screen">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </footer>
</body>

</html>