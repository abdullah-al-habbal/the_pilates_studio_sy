@extends('layouts.operations')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('admin.operations.classes.detail', $class) }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">← Back to class</a>
            <h2 class="mt-2 text-3xl font-bold tracking-tight">{{ __('dashboard.operations_ui.classes.edit') }}</h2>
            <p class="mt-1 text-slate-500">Update the class information, schedule, capacity, and review the generated sessions before saving.</p>
        </div>
        <div class="glass-card rounded-2xl p-6 md:p-8">
            <div id="class-wizard-page" data-class-id="{{ $class->id }}" data-class-detail-url="{{ route('admin.operations.classes.detail', $class) }}"></div>
        </div>
    </div>
@endsection
