<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class UserStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('widgets.users.heading');
    }

    protected function getStats(): array
    {
        return [
            $this->totalStat(),
            $this->activeStat(),
            $this->deactivatedStat(),
            $this->unverifiedStat(),
            $this->deletedStat(),
        ];
    }

    private const KEY_TOTAL = 'total';
    private const KEY_ACTIVE = 'active';
    private const KEY_DEACTIVATED = 'deactivated';
    private const KEY_UNVERIFIED = 'unverified';
    private const KEY_DELETED = 'deleted';

    private function data(): array
    {
        return Cache::remember('widget.users.stats', now()->addMinutes(10), function () {
            return [
                self::KEY_TOTAL       => User::withTrashed()->count(),
                self::KEY_ACTIVE      => User::whereNull('deactivated_at')->whereNull('deleted_at')->count(),
                self::KEY_DEACTIVATED => User::whereNotNull('deactivated_at')->whereNull('deleted_at')->count(),
                self::KEY_UNVERIFIED  => User::whereNull('email_verified_at')->whereNull('deleted_at')->count(),
                self::KEY_DELETED     => User::onlyTrashed()->count(),
            ];
        });
    }

    private function totalStat(): Stat
    {
        return Stat::make(__('widgets.users.total'), $this->data()[self::KEY_TOTAL])
            ->descriptionIcon('heroicon-m-users')
            ->color('gray');
    }

    private function activeStat(): Stat
    {
        return Stat::make(__('widgets.users.active'), $this->data()[self::KEY_ACTIVE])
            ->description(__('widgets.users.active_desc'))
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');
    }

    private function deactivatedStat(): Stat
    {
        return Stat::make(__('widgets.users.deactivated'), $this->data()[self::KEY_DEACTIVATED])
            ->description(__('widgets.users.deactivated_desc'))
            ->descriptionIcon('heroicon-m-no-symbol')
            ->color('warning');
    }

    private function unverifiedStat(): Stat
    {
        return Stat::make(__('widgets.users.unverified'), $this->data()[self::KEY_UNVERIFIED])
            ->description(__('widgets.users.unverified_desc'))
            ->descriptionIcon('heroicon-m-envelope')
            ->color('warning');
    }

    private function deletedStat(): Stat
    {
        return Stat::make(__('widgets.users.deleted'), $this->data()[self::KEY_DELETED])
            ->description(__('widgets.users.deleted_desc'))
            ->descriptionIcon('heroicon-m-trash')
            ->color('danger');
    }
}
