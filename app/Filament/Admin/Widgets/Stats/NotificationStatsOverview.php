<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Models\AppNotification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NotificationStatsOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('widgets.notifications.heading');
    }

    protected function getStats(): array
    {
        return [
            $this->totalStat(),
            $this->unreadStat(),
            $this->readStat(),
            $this->readRateStat(),
            $this->sentTodayStat(),
        ];
    }

    private function data(): array
    {
        $total = AppNotification::count();
        $read  = AppNotification::whereNotNull('read_at')->count();

        return [
            'total'      => $total,
            'read'       => $read,
            'unread'     => AppNotification::whereNull('read_at')->count(),
            'read_rate'  => $total > 0 ? round(($read / $total) * 100) : 0,
            'sent_today' => AppNotification::whereDate('created_at', today())->count(),
        ];
    }

    private function totalStat(): Stat
    {
        return Stat::make(__('widgets.notifications.total'), $this->data()['total'])
            ->descriptionIcon('heroicon-m-bell')
            ->color('gray');
    }

    private function unreadStat(): Stat
    {
        return Stat::make(__('widgets.notifications.unread'), $this->data()['unread'])
            ->description(__('widgets.notifications.unread_desc'))
            ->descriptionIcon('heroicon-m-bell-alert')
            ->color('warning');
    }

    private function readStat(): Stat
    {
        return Stat::make(__('widgets.notifications.read'), $this->data()['read'])
            ->description(__('widgets.notifications.read_desc'))
            ->descriptionIcon('heroicon-m-bell-slash')
            ->color('success');
    }

    private function readRateStat(): Stat
    {
        $rate = $this->data()['read_rate'];

        return Stat::make(__('widgets.notifications.read_rate'), $rate . '%')
            ->description(__('widgets.notifications.read_rate_desc'))
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color($rate >= 70 ? 'success' : 'warning');
    }

    private function sentTodayStat(): Stat
    {
        return Stat::make(__('widgets.notifications.sent_today'), $this->data()['sent_today'])
            ->descriptionIcon('heroicon-m-paper-airplane')
            ->color('info');
    }
}
