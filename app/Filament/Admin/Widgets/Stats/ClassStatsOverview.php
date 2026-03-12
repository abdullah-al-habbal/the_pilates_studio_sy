<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Enums\ClassSessionStatusEnum;
use App\Enums\ClassStatusEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ClassStatsOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('widgets.classes.heading');
    }

    protected function getStats(): array
    {
        return [
            $this->totalClassesStat(),
            $this->activeClassesStat(),
            $this->inactiveClassesStat(),
            $this->archivedClassesStat(),
            $this->todaySessionsStat(),
            $this->scheduledSessionsStat(),
            $this->completedSessionsStat(),
            $this->cancelledSessionsStat(),
            $this->instructorsStat(),
            $this->categoriesStat(),
        ];
    }

    private function classData(): array
    {
        return Cache::remember('widget.classes.stats', now()->addMinutes(10), function () {
            return [
                'total'                          => Classes::withTrashed()->count(),
                ClassStatusEnum::ACTIVE->value   => Classes::where('status', ClassStatusEnum::ACTIVE->value)->count(),
                ClassStatusEnum::INACTIVE->value => Classes::where('status', ClassStatusEnum::INACTIVE->value)->count(),
                ClassStatusEnum::ARCHIVED->value => Classes::where('status', ClassStatusEnum::ARCHIVED->value)->count(),
            ];
        });
    }

    private function sessionData(): array
    {
        return Cache::remember('widget.class_sessions.stats', now()->addMinutes(10), function () {
            return [
                ClassSessionStatusEnum::SCHEDULED->value => ClassSession::where('status', ClassSessionStatusEnum::SCHEDULED->value)->count(),
                ClassSessionStatusEnum::COMPLETED->value => ClassSession::where('status', ClassSessionStatusEnum::COMPLETED->value)->count(),
                ClassSessionStatusEnum::CANCELLED->value => ClassSession::where('status', ClassSessionStatusEnum::CANCELLED->value)->count(),
                'today'                                   => ClassSession::where('status', ClassSessionStatusEnum::SCHEDULED->value)->whereDate('date', today())->count(),
            ];
        });
    }

    private function configData(): array
    {
        return Cache::remember('widget.classes.config_counts', now()->addMinutes(30), function () {
            return [
                'instructors' => Instructor::count(),
                'categories'  => ClassCategory::count(),
            ];
        });
    }

    private function totalClassesStat(): Stat
    {
        return Stat::make(__('widgets.classes.total'), $this->classData()['total'])
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('gray');
    }

    private function activeClassesStat(): Stat
    {
        return Stat::make(__('widgets.classes.active'), $this->classData()[ClassStatusEnum::ACTIVE->value])
            ->description(__('widgets.classes.active_desc'))
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');
    }

    private function inactiveClassesStat(): Stat
    {
        return Stat::make(__('widgets.classes.inactive'), $this->classData()[ClassStatusEnum::INACTIVE->value])
            ->descriptionIcon('heroicon-m-pause-circle')
            ->color('warning');
    }

    private function archivedClassesStat(): Stat
    {
        return Stat::make(__('widgets.classes.archived'), $this->classData()[ClassStatusEnum::ARCHIVED->value])
            ->descriptionIcon('heroicon-m-archive-box')
            ->color('gray');
    }

    private function todaySessionsStat(): Stat
    {
        return Stat::make(__('widgets.classes.today'), $this->sessionData()['today'])
            ->description(__('widgets.classes.today_desc'))
            ->descriptionIcon('heroicon-m-sun')
            ->color('info');
    }

    private function scheduledSessionsStat(): Stat
    {
        return Stat::make(__('widgets.classes.scheduled'), $this->sessionData()[ClassSessionStatusEnum::SCHEDULED->value])
            ->description(__('widgets.classes.scheduled_desc'))
            ->descriptionIcon('heroicon-m-calendar')
            ->color('info');
    }

    private function completedSessionsStat(): Stat
    {
        return Stat::make(__('widgets.classes.completed'), $this->sessionData()[ClassSessionStatusEnum::COMPLETED->value])
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('success');
    }

    private function cancelledSessionsStat(): Stat
    {
        return Stat::make(__('widgets.classes.cancelled'), $this->sessionData()[ClassSessionStatusEnum::CANCELLED->value])
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');
    }

    private function instructorsStat(): Stat
    {
        return Stat::make(__('widgets.classes.instructors'), $this->configData()['instructors'])
            ->descriptionIcon('heroicon-m-user-circle')
            ->color('gray');
    }

    private function categoriesStat(): Stat
    {
        return Stat::make(__('widgets.classes.categories'), $this->configData()['categories'])
            ->descriptionIcon('heroicon-m-tag')
            ->color('gray');
    }
}
