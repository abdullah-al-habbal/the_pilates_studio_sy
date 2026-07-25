{{-- resources/views/landing/index.blade.php --}}
@extends('layouts.landing')

@section('content')
    @if($landingData->hasError)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 p-4 rounded-lg m-4">
            {{ __('landing.error_generic') }}
        </div>
    @endif
    @include('landing.partials._hero')
    @include('landing.partials._features')
    @include('landing.partials._classes')
    @include('landing.partials._schedule')
    @include('landing.partials._instructors')
    @include('landing.partials._packages')
    @include('landing.partials._how-it-works')
    @include('landing.partials._testimonials')
    @include('landing.partials._cta')
@endsection

@push('scripts')
<script>
    // Smooth scroll to hash if present on page load
    if (window.location.hash) {
        window.addEventListener('DOMContentLoaded', function () {
            const target = document.querySelector(window.location.hash);
            if (target) {
                setTimeout(() => {
                    target.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            }
        });
    }

    // Smooth scroll for same-page hash clicks when already on landing
    document.querySelectorAll('a[href^="{{ route('landing') }}#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (window.location.pathname === '{{ parse_url(route('landing'), PHP_URL_PATH) }}' || window.location.pathname === new URL(link.href).pathname) {
                const hash = new URL(link.href).hash;
                if (hash) {
                    e.preventDefault();
                    const target = document.querySelector(hash);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                        history.pushState(null, null, hash);
                    }
                }
            }
        });
    });
</script>
@endpush
