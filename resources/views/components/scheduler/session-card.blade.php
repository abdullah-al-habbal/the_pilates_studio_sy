@props(['session', 'attendanceAction' => null])

@php
$locale = app()->getLocale();
$title = $session->class?->title[$locale] ?? $session->class?->title['en'] ?? '—';
$instructor = $session->class?->instructor?->fullname ?? __('dashboard.pages.scheduler.no_instructor');
$start = substr($session->start_time, 0, 5);
$end = substr($session->end_time, 0, 5);
$attended = $session->bookingSessions->where('attendance_status.value', 'attended')->count();
$total = $session->bookingSessions->count();
$capacity = $session->total_spots ?? 0;
$isFull = $capacity > 0 && $total >= $capacity;
@endphp

<div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col overflow-hidden">

    <div class="bg-primary-50 dark:bg-primary-950 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
        <p class="font-bold text-gray-900 dark:text-gray-100 text-base leading-tight">{{ $title }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1">
            <x-heroicon-o-user style="width:.9rem;height:.9rem;" />
            {{ $instructor }}
        </p>
    </div>

    <div class="px-5 py-4 flex-1 space-y-3">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-heroicon-o-clock style="width:.9rem;height:.9rem;" />
            <span>{{ $start }} – {{ $end }}</span>
        </div>

        <div class="flex items-center gap-2 text-sm">
            <x-heroicon-o-users style="width:.9rem;height:.9rem;" class="text-gray-400" />
            <span class="font-medium text-gray-700 dark:text-gray-300">
                {{ $attended }} / {{ $total }}
                @if($capacity > 0)
                    <span class="text-gray-400">(cap {{ $capacity }})</span>
                @endif
            </span>
            @if($isFull)
                <span class="ml-auto text-xs bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300 px-2 py-0.5 rounded-full font-medium">
                    {{ __('dashboard.pages.scheduler.session_full') }}
                </span>
            @endif
        </div>

        @if($capacity > 0)
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $isFull ? 'bg-danger-500' : 'bg-primary-500' }}"
                    style="width: {{ min(100, ($total / $capacity) * 100) }}%">
                </div>
            </div>
        @endif
    </div>

    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
        @if($attendanceAction)
            <div class="[&_.fi-ac-btn-label]:w-full">
                <x-filament-actions::actions :actions="[$attendanceAction]" />
            </div>
        @endif
    </div>
</div>
