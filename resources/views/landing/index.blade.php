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
