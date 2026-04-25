{{-- resources/views/layouts/scheduler.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scheduler – {{ config('app.name') }}</title>

    <script>
        if (localStorage.getItem('scheduler-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased min-h-screen">

    @yield('content')

    <div id="toaster-stack" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
        aria-live="polite" aria-atomic="false"></div>

    <script src="{{ asset('js/scheduler/state.js') }}"></script>
    <script src="{{ asset('js/scheduler/ui.js') }}"></script>
    <script src="{{ asset('js/scheduler/toaster.js') }}"></script>
    <script src="{{ asset('js/scheduler/api.js') }}"></script>
    <script src="{{ asset('js/scheduler/templates.js') }}"></script>
    <script src="{{ asset('js/scheduler/render.js') }}"></script>
    <script src="{{ asset('js/scheduler/modal.js') }}"></script>
    <script src="{{ asset('js/scheduler/walkin.js') }}"></script>
    <script src="{{ asset('js/scheduler/events.js') }}"></script>
    <script src="{{ asset('js/scheduler/main.js') }}"></script>
</body>

</html>