<?php

namespace App\Filament\Admin\Pages;

use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\Merchandise\MerchandiseOrderEloquentRepository;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

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

    protected string $view = 'filament.admin.pages.reports';

    public string $period = 'all';

    public function getPeriodDates(): array
    {
        return match ($this->period) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'yearly' => [now()->startOfYear(), now()->endOfYear()],
            default => [null, null],
        };
    }

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

        [$startDate, $endDate] = $this->getPeriodDates();

        $bookingRevenue = $bookingRepo->getTotalRevenue($startDate, $endDate);
        $merchandiseRevenue = $merchandiseRepo->getTotalRevenue($startDate, $endDate);

        return [
            'booking_revenue' => $bookingRevenue,
            'merchandise_revenue' => $merchandiseRevenue,
            'total_revenue' => $bookingRevenue + $merchandiseRevenue,
            'total_bookings' => $bookingRepo->getTotalCount($startDate, $endDate),
            'total_merchandise_orders' => $merchandiseRepo->getTotalCount($startDate, $endDate),
        ];
    }

    protected function getPopularClasses(): \Illuminate\Support\Collection
    {
        $classesRepo = App::make(ClassesEloquentRepository::class);

        [$startDate, $endDate] = $this->getPeriodDates();

        return $classesRepo->getPopularClassesSummary(5, $startDate, $endDate);
    }

    protected function getMerchandiseSales(): \Illuminate\Support\Collection
    {
        $merchandiseRepo = App::make(MerchandiseOrderEloquentRepository::class);

        [$startDate, $endDate] = $this->getPeriodDates();

        return $merchandiseRepo->getTopSellingSummary(5, $startDate, $endDate);
    }
}
