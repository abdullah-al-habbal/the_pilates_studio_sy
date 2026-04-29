<?php

// filePath: app\Providers\Filament\AdminPanelProvider.php
declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\AttendanceTrendChart;
use App\Filament\Admin\Widgets\CategoryPerformanceWidget;
use App\Filament\Admin\Widgets\Stats\{
    BookingStatsOverview,
    ClassStatsOverview,
    InsightsStatsOverview,
    NotificationStatsOverview,
    TopPerformersStatsOverview,
    UserStatsOverview,
};
use App\Filament\Admin\Widgets\StatsOverview;
use App\Filament\Admin\Widgets\TopInstructorsWidget;
use Filament\Auth\Pages\EditProfile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Illuminate\Support\Facades\Blade;
use Filament\Navigation\NavigationItem;
use App\Models\ClassSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->login()
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->profile(EditProfile::class)
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->plugins([
                SpatieTranslatablePlugin::make()
                    ->persist()
                    ->defaultLocales([
                        'en',
                        'ar',
                    ]),
            ])
            ->widgets([
                StatsOverview::class,
                AttendanceTrendChart::class,
                CategoryPerformanceWidget::class,
                TopInstructorsWidget::class,
                BookingStatsOverview::class,
                ClassStatsOverview::class,
                InsightsStatsOverview::class,
                NotificationStatsOverview::class,
                TopPerformersStatsOverview::class,
                UserStatsOverview::class,
            ])
            ->middleware($this->getMiddleware())
            ->authMiddleware($this->getAuthMiddleware())
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => Blade::render('
                    <div class="flex items-center gap-x-3 mr-4">
                        <x-filament::button
                            href="/admin/scheduler"
                            tag="a"
                            size="sm"
                            icon="heroicon-m-calendar-days"
                            color="gray"
                        >
                            ' . __('dashboard.navigation.scheduler') . '
                        </x-filament::button>
                        <x-filament::button
                            href="/admin/operations"
                            tag="a"
                            size="sm"
                            icon="heroicon-m-cog-6-tooth"
                            color="primary"
                        >
                            ' . __('dashboard.navigation.groups.operations') . '
                        </x-filament::button>
                    </div>
                '),
            )
            ->navigationItems([
                NavigationItem::make(__('dashboard.navigation.scheduler'))
                    ->url('/admin/scheduler')
                    ->icon('heroicon-o-calendar-days')
                    ->sort(1)
                    ->group(__('dashboard.navigation.groups.operations'))
                    ->badge(fn(): string => (string) cache()->remember(
                        'filament.scheduler.today_count',
                        now()->addMinutes(5),
                        fn() => ClassSession::where('status', 'scheduled')
                            ->whereDate('date', today())
                            ->count()
                    ), color: fn(): string => ClassSession::where('status', 'scheduled')
                            ->whereDate('date', today())
                            ->count() > 0 ? 'primary' : 'gray')
                    ->isActiveWhen(fn(): bool => request()->is('admin/scheduler*')),

                NavigationItem::make(__('dashboard.navigation.groups.operations'))
                    ->url('/admin/operations')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->sort(2)
                    ->group(__('dashboard.navigation.groups.operations'))
                    ->isActiveWhen(fn(): bool => request()->is('admin/operations*')),
            ]);
    }

    private function getMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }

    private function getAuthMiddleware(): array
    {
        return [
            Authenticate::class,
            AuthenticateSession::class,
        ];
    }
}
