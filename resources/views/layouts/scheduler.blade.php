{{-- resources/views/layouts/scheduler.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('dashboard.scheduler_ui.header.dashboard') }} – {{ config('app.name') }}</title>

    <script>
        if (localStorage.getItem('scheduler-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased min-h-screen">

    @yield('content')

    <div id="toaster-stack" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
        aria-live="polite" aria-atomic="false"></div>

    @php
        // Keyed WITH the scheduler_ui prefix, because that is how the JS asks for them:
        // window.__('scheduler_ui.templates.manage_button'). Flattening the inner array
        // alone yielded 'templates.manage_button', so every JS lookup missed and
        // rendered its own key instead of the translation.
        $schedulerTranslations = \Illuminate\Support\Arr::dot([
            'scheduler_ui' => __('dashboard.scheduler_ui'),
        ]);
    @endphp
    <script>
        // Hoisted out of the closure: this literal used to be re-materialised on every __()
        // call, so every new key taxed every existing lookup.
        window.SchedulerTranslations = @json($schedulerTranslations);
        window.__ = function(key) {
            return window.SchedulerTranslations[key] || key;
        };
    </script>

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