<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Dashboard\StatsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    public function __construct(private readonly StatsService $statsService)
    {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $statsData = $this->statsService->getOverviewStats();

        return [
            Stat::make(__('dashboard.widgets.stats_overview.active_users'), number_format($statsData['total_active_users']))
                ->description(__('dashboard.widgets.stats_overview.active_users_description'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make(__('dashboard.widgets.stats_overview.active_bookings'), number_format($statsData['active_bookings']))
                ->description(__('dashboard.widgets.stats_overview.active_bookings_description'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make(__('dashboard.widgets.stats_overview.credits_sold'), number_format($statsData['total_credits_sold']))
                ->description(__('dashboard.widgets.stats_overview.credits_sold_description'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),

            Stat::make(__('dashboard.widgets.stats_overview.credits_consumed'), number_format($statsData['credits_consumed']))
                ->description($statsData['total_credits_sold'] > 0
                    ? __('dashboard.widgets.stats_overview.credits_consumed_usage', ['percentage' => round(($statsData['credits_consumed'] / $statsData['total_credits_sold']) * 100)])
                    : __('dashboard.widgets.stats_overview.credits_consumed_usage', ['percentage' => 0]))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make(__('dashboard.widgets.stats_overview.attendance_rate'), $statsData['attendance_rate'] . '%')
                ->description(__('dashboard.widgets.stats_overview.attendance_rate_description'))
                ->descriptionIcon('heroicon-m-calendar')
                ->chart($statsData['attendance_trend'])
                ->color($statsData['attendance_rate'] >= 70 ? 'success' : 'danger'),

            Stat::make(__('dashboard.widgets.stats_overview.missed'), $statsData['missed'])
                ->description(__('dashboard.widgets.stats_overview.missed_description', ['trend' => ($statsData['missed_trend'] > 0 ? '+' : '') . $statsData['missed_trend']]))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($statsData['missed_trend'] > 0 ? 'danger' : 'success'),

            Stat::make(__('dashboard.widgets.stats_overview.upcoming_full_sessions'), $statsData['upcoming_full_sessions'])
                ->description(__('dashboard.widgets.stats_overview.upcoming_full_sessions_description'))
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make(__('dashboard.navigation.scheduler'), '')
                ->description('Quick access to today\'s schedule and attendance')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->url('/admin/scheduler'),

            Stat::make(__('dashboard.navigation.groups.operations'), '')
                ->description('Manage client packages, store orders, and finances')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('primary')
                ->url('/admin/operations'),
        ];
    }
}
