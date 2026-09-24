@extends('layouts.operations')

@section('content')
    @php
        $locale = app()->getLocale();
        $title = $class->getTranslation('title', $locale, false) ?: $class->getTranslation('title', 'en');
        $isWeekdaySchedule = $class->hasWeekdaySchedule();
        $scheduleLabel = $isWeekdaySchedule
            ? collect($class->weekdayCases())->map(fn ($day) => $day->getLabel())->join(', ')
            : ($class->recurrencePattern?->getTranslation('label', $locale, false) ?: $class->recurrencePattern?->getTranslation('label', 'en'));
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('admin.operations.index') }}#classes" class="text-sm font-semibold text-primary-600 hover:text-primary-700">← {{ __('dashboard.operations_ui.classes.title') }}</a>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">{{ $title }}</h2>
                <p class="mt-1 text-slate-500">{{ $class->category?->getTranslation('name', $locale, false) ?: $class->category?->getTranslation('name', 'en') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @unless($class->trashed())
                    <a href="{{ route('admin.operations.classes.edit-page', $class) }}" class="rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-700">{{ __('dashboard.operations_ui.classes.edit') }}</a>
                    <button type="button" data-detail-class-action="status" data-class-id="{{ $class->id }}" data-status="{{ $class->status->value === 'active' ? 'inactive' : 'active' }}" class="rounded-xl border border-amber-300 px-4 py-2.5 text-sm font-bold text-amber-700 hover:bg-amber-50">{{ $class->status->value === 'active' ? __('dashboard.operations_ui.classes.deactivate') : __('dashboard.operations_ui.classes.activate') }}</button>
                    <button type="button" data-detail-class-action="delete" data-class-id="{{ $class->id }}" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-700">{{ __('dashboard.operations_ui.classes.delete') }}</button>
                @endunless
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="glass-card rounded-2xl p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.operations_ui.classes.category') }}</p><p class="mt-2 font-bold">{{ $class->category?->getTranslation('name', $locale, false) ?: $class->category?->getTranslation('name', 'en') ?: '—' }}</p></div>
            <div class="glass-card rounded-2xl p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.operations_ui.classes.instructor') }}</p><p class="mt-2 font-bold">{{ $class->instructor?->getTranslation('name', $locale, false) ?: $class->instructor?->getTranslation('name', 'en') ?: __('dashboard.operations_ui.classes.no_instructor') }}</p></div>
            <div class="glass-card rounded-2xl p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.operations_ui.classes.capacity') }}</p><p class="mt-2 font-bold">{{ $class->total_spots }} {{ __('dashboard.operations_ui.classes.spots') }}</p></div>
            <div class="glass-card rounded-2xl p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('dashboard.operations_ui.classes.bookings') }}</p><p class="mt-2 font-bold">{{ $class->booking_sessions_count }}</p></div>
        </div>

        <div class="glass-card rounded-2xl p-6">
            <h3 class="text-lg font-bold">{{ __('dashboard.operations_ui.classes.schedule') }}</h3>
            <p class="mt-2 text-slate-600 dark:text-slate-300">{{ $scheduleLabel ?: '—' }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $class->start_date?->toDateString() }} – {{ $class->end_date?->toDateString() }} · {{ \Carbon\Carbon::parse($class->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($class->end_time)->format('g:i A') }}</p>
        </div>

        @if(filled($class->getTranslation('about', $locale, false) ?: $class->getTranslation('about', 'en')))
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold">About this class</h3>
                <p class="mt-3 whitespace-pre-line text-slate-600 dark:text-slate-300">{{ strip_tags($class->getTranslation('about', $locale, false) ?: $class->getTranslation('about', 'en')) }}</p>
            </div>
        @endif

        @if($class->images->isNotEmpty())
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-bold">Images</h3>
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($class->images as $image)
                        <img src="{{ $image->image_url }}" alt="" class="aspect-square w-full rounded-xl object-cover shadow-sm">
                    @endforeach
                </div>
            </div>
        @endif

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <div><h3 class="text-lg font-bold">{{ __('dashboard.operations_ui.classes.sessions') }}</h3><p class="text-sm text-slate-500">{{ trans_choice(':count session|:count sessions', $sessions->total(), ['count' => $sessions->total()]) }}</p></div>
                <a href="{{ route('admin.operations.classes.edit-page', $class) }}" class="text-sm font-bold text-primary-600 hover:text-primary-700">{{ __('dashboard.operations_ui.classes.edit') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800/50"><tr><th class="px-6 py-4">Date</th><th class="px-6 py-4">Time</th><th class="px-6 py-4">Capacity</th><th class="px-6 py-4">Bookings</th><th class="px-6 py-4">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($sessions as $session)
                            <tr><td class="px-6 py-4 font-semibold">{{ $session->date->toDateString() }}</td><td class="px-6 py-4">{{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('g:i A') }}</td><td class="px-6 py-4">{{ $session->total_spots }}</td><td class="px-6 py-4">{{ $session->booking_sessions_count }}</td><td class="px-6 py-4"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $session->status->value }}</span></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">{{ __('dashboard.operations_ui.classes.no_sessions') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">{{ $sessions->links() }}</div>
            @endif
        </div>
    </div>
@endsection
