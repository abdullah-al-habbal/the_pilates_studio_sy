<?php

// filePath: app\Providers\Filament\AdminPanelProvider.php
declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\AttendanceTrendChart;
use App\Filament\Admin\Widgets\CategoryPerformanceWidget;
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
            ])
            ->middleware($this->getMiddleware())
            ->authMiddleware($this->getAuthMiddleware());
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
