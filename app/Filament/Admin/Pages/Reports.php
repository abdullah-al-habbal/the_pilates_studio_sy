<?php

namespace App\Filament\Admin\Pages;

use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\Merchandise\MerchandiseOrderEloquentRepository;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation.reports');
    }

    public function getHeading(): string
    {
        return __('dashboard.pages.reports.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.operations');
    }

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.reports';

    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'popularClasses' => $this->getPopularClasses(),
            'merchandiseSales' => $this->getMerchandiseSales(),
        ];
    }

    protected function getStats(): array
    {
        $bookingRepo = App::make(BookingEloquentRepository::class);
        $merchandiseRepo = App::make(MerchandiseOrderEloquentRepository::class);

        $bookingRevenue = $bookingRepo->getTotalRevenue();
        $merchandiseRevenue = $merchandiseRepo->getTotalRevenue();

        return [
            'booking_revenue' => $bookingRevenue,
            'merchandise_revenue' => $merchandiseRevenue,
            'total_revenue' => $bookingRevenue + $merchandiseRevenue,
            'total_bookings' => $bookingRepo->getTotalCount(),
            'total_merchandise_orders' => $merchandiseRepo->getTotalCount(),
        ];
    }

    protected function getPopularClasses(): \Illuminate\Support\Collection
    {
        $classesRepo = App::make(ClassesEloquentRepository::class);

        return $classesRepo->getPopularClassesSummary(5);
    }

    protected function getMerchandiseSales(): \Illuminate\Support\Collection
    {
        $merchandiseRepo = App::make(MerchandiseOrderEloquentRepository::class);

        return $merchandiseRepo->getTopSellingSummary(5);
    }
}
