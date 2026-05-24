<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Models\ClassSession;
use App\Models\Package;
use App\Models\RecurrencePattern;
use App\Models\UserSetting;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InsightsStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected static ?int $sort = 5;

    protected function getHeading(): ?string
    {
        return __('widgets.insights.heading');
    }

    protected function getStats(): array
    {
        return [
            $this->notificationsOptInStat(),
            $this->mostUsedPackageStat(),
            $this->mostUsedRecurrenceStat(),
            $this->averageSpotsStat(),
            $this->peakSessionTimeStat(),
            $this->averageSessionDurationStat(),
        ];
    }

    private function optInData(): array
    {
        $total   = UserSetting::count();
        $enabled = UserSetting::where('allow_notifications', true)->count();

        return [
            'total'   => $total,
            'enabled' => $enabled,
            'rate'    => $total > 0 ? round(($enabled / $total) * 100) : 0,
        ];
    }

    private function topPackageData(): array
    {
        $package = Package::withCount('bookings')->orderByDesc('bookings_count')->first();

        return [
            'name'  => $package?->getTranslation('name', app()->getLocale()) ?? __('widgets.insights.na'),
            'count' => $package?->bookings_count ?? 0,
        ];
    }

    private function topRecurrenceData(): array
    {
        $pattern = RecurrencePattern::withCount('classes')->orderByDesc('classes_count')->first();

        return [
            'label' => $pattern?->getTranslation('label', app()->getLocale()) ?? __('widgets.insights.na'),
            'count' => $pattern?->classes_count ?? 0,
        ];
    }

    private function sessionMetricsData(): array
    {
        $avgSpots = round(ClassSession::avg('total_spots') ?? 0, 1);

        $peak = ClassSession::select('start_time', DB::raw('count(*) as total'))
            ->groupBy('start_time')
            ->orderByDesc('total')
            ->first();

        $avgDuration = ClassSession::select('start_time', 'end_time')->get()->avg(
            fn($s) => Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time))
        );

        return [
            'avg_spots'        => $avgSpots,
            'peak_time'        => $peak ? Carbon::parse($peak->start_time)->format('H:i') : null,
            'peak_count'       => $peak?->total ?? 0,
            'avg_duration'     => round($avgDuration ?? 0),
        ];
    }

    private function notificationsOptInStat(): Stat
    {
        $d = $this->optInData();

        return Stat::make(
            __('widgets.insights.notifications_optin'),
            $d['rate'] . '%'
        )
            ->description(__('widgets.insights.notifications_optin_desc', ['enabled' => $d['enabled'], 'total' => $d['total']]))
            ->descriptionIcon('heroicon-m-bell')
            ->color($d['rate'] >= 60 ? 'success' : 'warning');
    }

    private function mostUsedPackageStat(): Stat
    {
        $d     = $this->topPackageData();
        $label = $d['name'] . ' (' . $d['count'] . '×)';

        return Stat::make(__('widgets.insights.most_booked_package'), $label)
            ->description(__('widgets.insights.most_booked_package_desc'))
            ->descriptionIcon('heroicon-m-gift')
            ->color('success');
    }

    private function mostUsedRecurrenceStat(): Stat
    {
        $d     = $this->topRecurrenceData();
        $label = $d['label'] . ' (' . $d['count'] . ')';

        return Stat::make(__('widgets.insights.most_used_recurrence'), $label)
            ->description(__('widgets.insights.most_used_recurrence_desc'))
            ->descriptionIcon('heroicon-m-arrow-path')
            ->color('info');
    }

    private function averageSpotsStat(): Stat
    {
        return Stat::make(__('widgets.insights.avg_spots'), $this->sessionMetricsData()['avg_spots'])
            ->description(__('widgets.insights.avg_spots_desc'))
            ->descriptionIcon('heroicon-m-user-group')
            ->color('gray');
    }

    private function peakSessionTimeStat(): Stat
    {
        $d = $this->sessionMetricsData();

        $label = $d['peak_time']
            ? $d['peak_time'] . ' (' . $d['peak_count'] . ')'
            : __('widgets.insights.na');

        return Stat::make(__('widgets.insights.peak_time'), $label)
            ->description(__('widgets.insights.peak_time_desc'))
            ->descriptionIcon('heroicon-m-clock')
            ->color('info');
    }

    private function averageSessionDurationStat(): Stat
    {
        $value = __('widgets.insights.avg_duration_value', ['value' => $this->sessionMetricsData()['avg_duration']]);

        return Stat::make(__('widgets.insights.avg_duration'), $value)
            ->description(__('widgets.insights.avg_duration_desc'))
            ->descriptionIcon('heroicon-m-play')
            ->color('gray');
    }
}
