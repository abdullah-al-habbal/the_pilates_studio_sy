@extends('layouts.scheduler')
@section('content')
    <style>
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }
    </style>
    <div id="scheduler-root"
        class="min-h-screen flex flex-col bg-gray-50/50 dark:bg-gray-950/50 transition-colors duration-500">
        @include('admin.scheduler.partials.header')
        @include('admin.scheduler.partials.main')
        @include('admin.scheduler.partials.modal')
    </div>
@endsection