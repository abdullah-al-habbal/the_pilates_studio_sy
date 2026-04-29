<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Operations Hub – {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        primary: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e', 950: '#082f49' },
                        gold: { 50: '#fdfcf7', 100: '#fbf7e6', 200: '#f5ea9d', 300: '#f0dd55', 400: '#eacc0d', 500: '#d4b90c', 600: '#ba9a0a', 700: '#a07d09', 800: '#866008', 900: '#6c4307', 950: '#522606' }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .dark .glass-card { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .gradient-bg { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased min-h-screen">
    
    <nav class="sticky top-0 z-50 glass-card border-b px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-primary-500/30">P</div>
            <h1 class="text-xl font-bold tracking-tight">Operations <span class="text-primary-500">Hub</span></h1>
        </div>
        <div class="flex items-center gap-4">
            <div id="connection-status" class="flex items-center gap-2 text-xs font-medium px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Connected
            </div>
            <button id="theme-toggle" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            </button>
        </div>
    </nav>

    <main class="max-w-[1600px] mx-auto p-6">
        @yield('content')
    </main>

    <!-- Global Modal Overlay -->
    <div id="modal-overlay" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
        <div id="modal-container" class="glass-card rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl scale-95 opacity-0 transition-all duration-300">
            <!-- Modal content injected here -->
        </div>
    </div>

    <!-- Toaster Stack -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-3"></div>

    <script src="{{ asset('js/operations/api.js') }}"></script>
    <script src="{{ asset('js/operations/ui.js') }}"></script>
    <script src="{{ asset('js/operations/main.js') }}"></script>
</body>
</html>
