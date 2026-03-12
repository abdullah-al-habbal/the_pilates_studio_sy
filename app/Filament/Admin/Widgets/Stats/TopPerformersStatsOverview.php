<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Enums\BookingSessionStatusEnum;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\Instructor;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopPerformersStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected static ?int $sort = 7;

    protected function getHeading(): ?string
    {
        return __('widgets.top_performers.heading');
    }

    protected function getStats(): array
    {
        return [
            $this->topInstructorStat(),
            $this->topClassBySessionsStat(),
            $this->topClassByAttendanceStat(),
            $this->topCategoriesStat(),
            $this->topUserByBookingsStat(),
            $this->topUserByAttendanceStat(),
        ];
    }

    private function topInstructorData(): array
    {
        return Cache::remember('widget.top.instructor', now()->addMinutes(15), function () {
            $instructor = Instructor::withCount('classes')->orderByDesc('classes_count')->first();

            $attendance = $instructor
                ? BookingSession::where('status', BookingSessionStatusEnum::ATTENDED->value)
                ->whereHas('classSession.class', fn($q) => $q->where('instructor_id', $instructor->id))
                ->count()
                : 0;

            return [
                'name'       => $instructor?->getTranslation('name', app()->getLocale()) ?? __('widgets.top_performers.na'),
                'classes'    => $instructor?->classes_count ?? 0,
                'attendance' => $attendance,
            ];
        });
    }

    private function topClassBySessionsData(): array
    {
        return Cache::remember('widget.top.class_sessions', now()->addMinutes(15), function () {
            $class = Classes::withCount('sessions')->orderByDesc('sessions_count')->first();

            return [
                'title' => $class?->getTranslation('title', app()->getLocale()) ?? __('widgets.top_performers.na'),
                'count' => $class?->sessions_count ?? 0,
            ];
        });
    }

    private function topClassByAttendanceData(): array
    {
        return Cache::remember('widget.top.class_attendance', now()->addMinutes(15), function () {
            $result = BookingSession::select('class_sessions.class_id', DB::raw('count(*) as attendance_count'))
                ->join('class_sessions', 'booking_sessions.class_session_id', '=', 'class_sessions.id')
                ->where('booking_sessions.status', BookingSessionStatusEnum::ATTENDED->value)
                ->groupBy('class_sessions.class_id')
                ->orderByDesc('attendance_count')
                ->first();

            $class = $result ? Classes::find($result->class_id) : null;

            return [
                'title' => $class?->getTranslation('title', app()->getLocale()) ?? __('widgets.top_performers.na'),
                'count' => $result?->attendance_count ?? 0,
            ];
        });
    }

    private function topCategoriesData(): array
    {
        return Cache::remember('widget.top.categories', now()->addMinutes(15), function () {
            return ClassCategory::withCount('classes')
                ->orderByDesc('classes_count')
                ->limit(3)
                ->get()
                ->map(fn($c) => [
                    'name'  => $c->getTranslation('name', app()->getLocale()),
                    'count' => $c->classes_count,
                ])
                ->toArray();
        });
    }

    private function topUserByBookingsData(): array
    {
        return Cache::remember('widget.top.user_bookings', now()->addMinutes(15), function () {
            $user = User::withCount('bookings')->orderByDesc('bookings_count')->first();

            return [
                'name'  => $user?->fullname ?? __('widgets.top_performers.na'),
                'count' => $user?->bookings_count ?? 0,
            ];
        });
    }

    private function topUserByAttendanceData(): array
    {
        return Cache::remember('widget.top.user_attendance', now()->addMinutes(15), function () {
            $result = BookingSession::select('bookings.user_id', DB::raw('count(*) as attended_count'))
                ->join('bookings', 'booking_sessions.booking_id', '=', 'bookings.id')
                ->where('booking_sessions.status', BookingSessionStatusEnum::ATTENDED->value)
                ->groupBy('bookings.user_id')
                ->orderByDesc('attended_count')
                ->first();

            $user = $result ? User::find($result->user_id) : null;

            return [
                'name'  => $user?->fullname ?? __('widgets.top_performers.na'),
                'count' => $result?->attended_count ?? 0,
            ];
        });
    }

    private function topInstructorStat(): Stat
    {
        $d = $this->topInstructorData();

        return Stat::make(
            __('widgets.top_performers.top_instructor'),
            $d['name'] . ' (' . __('widgets.top_performers.classes_suffix', ['count' => $d['classes']]) . ')'
        )
            ->description(__('widgets.top_performers.top_instructor_desc', ['count' => $d['attendance']]))
            ->descriptionIcon('heroicon-m-user-circle')
            ->color('success');
    }

    private function topClassBySessionsStat(): Stat
    {
        $d = $this->topClassBySessionsData();

        return Stat::make(
            __('widgets.top_performers.most_sessions'),
            $d['title'] . ' (' . __('widgets.top_performers.sessions_suffix', ['count' => $d['count']]) . ')'
        )
            ->description(__('widgets.top_performers.most_sessions_desc'))
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('info');
    }

    private function topClassByAttendanceStat(): Stat
    {
        $d = $this->topClassByAttendanceData();

        return Stat::make(
            __('widgets.top_performers.top_attendance'),
            $d['title'] . ' (' . __('widgets.top_performers.attended_suffix', ['count' => $d['count']]) . ')'
        )
            ->description(__('widgets.top_performers.top_attendance_desc'))
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('success');
    }

    private function topCategoriesStat(): Stat
    {
        $categories = $this->topCategoriesData();
        $top        = $categories[0] ?? null;

        $description = collect($categories)
            ->map(fn($c) => $c['name'] . ': ' . $c['count'])
            ->implode(' · ');

        $label = $top
            ? $top['name'] . ' (' . __('widgets.top_performers.classes_suffix', ['count' => $top['count']]) . ')'
            : __('widgets.top_performers.na');

        return Stat::make(__('widgets.top_performers.top_categories'), $label)
            ->description($description)
            ->descriptionIcon('heroicon-m-tag')
            ->color('info');
    }

    private function topUserByBookingsStat(): Stat
    {
        $d = $this->topUserByBookingsData();

        return Stat::make(
            __('widgets.top_performers.most_packages'),
            $d['name'] . ' (' . __('widgets.top_performers.packages_suffix', ['count' => $d['count']]) . ')'
        )
            ->description(__('widgets.top_performers.most_packages_desc'))
            ->descriptionIcon('heroicon-m-credit-card')
            ->color('success');
    }

    private function topUserByAttendanceStat(): Stat
    {
        $d = $this->topUserByAttendanceData();

        return Stat::make(
            __('widgets.top_performers.most_active'),
            $d['name'] . ' (' . __('widgets.top_performers.attended_suffix', ['count' => $d['count']]) . ')'
        )
            ->description(__('widgets.top_performers.most_active_desc'))
            ->descriptionIcon('heroicon-m-star')
            ->color('success');
    }
}
