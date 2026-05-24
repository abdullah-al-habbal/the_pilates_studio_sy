<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Models\Language;
use App\Models\UserSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LanguageStatsOverview extends BaseWidget
{
    protected static ?int $sort = 6;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('widgets.language.heading');
    }

    protected function getStats(): array
    {
        return $this->languageStats();
    }

    private function data(): array
    {
        $total = UserSetting::count();

        return [
            'total'     => $total,
            'languages' => Language::where('is_active', true)
                ->withCount('userSettings')
                ->orderByDesc('user_settings_count')
                ->get()
                ->map(fn(Language $lang) => [
                    'name'       => $lang->name,
                    'count'      => $lang->user_settings_count,
                    'rate'       => $total > 0 ? round(($lang->user_settings_count / $total) * 100) : 0,
                    'direction'  => $lang->direction,
                    'is_default' => $lang->is_default,
                ])
                ->toArray(),
        ];
    }

    private function languageStats(): array
    {
        return collect($this->data()['languages'])
            ->map(function (array $lang) {
                $dirLabel = $lang['direction'] === 'rtl'
                    ? __('widgets.language.rtl')
                    : __('widgets.language.ltr');

                return Stat::make($lang['name'], $lang['rate'] . '%')
                    ->description(__('widgets.language.users_count', ['count' => $lang['count']]) . ' · ' . $dirLabel)
                    ->descriptionIcon('heroicon-m-language')
                    ->color($lang['is_default'] ? 'success' : 'info');
            })
            ->toArray();
    }
}
