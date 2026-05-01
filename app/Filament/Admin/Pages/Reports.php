<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\Merchandise\MerchandiseOrderEloquentRepository;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class Reports extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected string $view = 'filament.admin.pages.reports';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember(
            'filament.reports.today_revenue',
            now()->addMinutes(5),
            function () {
                $repo = App::make(MerchandiseOrderEloquentRepository::class);
                $total = $repo->getTotalRevenue(now()->startOfDay(), now()->endOfDay());

                // fix: use the correct currecny code
                return number_format($total, 0) . ' SYP';
            }
        );
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public string $period = 'daily';

    public string $dailyDate = '';

    public string $month = '';

    public string $year = '';

    public string $customStart = '';

    public string $customEnd = '';

    private ?array $_stats = null;

    private ?Collection $_classes = null;

    private ?Collection $_merch = null;

    public function mount(): void
    {
        $this->dailyDate = now()->toDateString();
        $this->month = now()->format('Y-m');
        $this->year = now()->format('Y');
    }

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

    public function getPeriodDates(): array
    {
        return match ($this->period) {
            'daily' => [
                Carbon::parse($this->dailyDate)->startOfDay(),
                Carbon::parse($this->dailyDate)->endOfDay(),
            ],
            'monthly' => [
                Carbon::parse($this->month . '-01')->startOfMonth(),
                Carbon::parse($this->month . '-01')->endOfMonth(),
            ],
            'yearly' => [
                Carbon::create((int) $this->year)->startOfYear(),
                Carbon::create((int) $this->year)->endOfYear(),
            ],
            'custom' => [
                $this->customStart ? Carbon::parse($this->customStart)->startOfDay() : null,
                $this->customEnd ? Carbon::parse($this->customEnd)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    public function reportsInfolist(Schema $schema): Schema
    {
        $stats = $this->stats();
        $classes = $this->popularClasses();
        $merch = $this->merchandiseSales();

        return $schema->components([

            Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 5])
                ->schema([
                    Section::make()
                        ->schema([
                            // fix: check the calculation and we must make the revenues in the prices
                            TextEntry::make('total_revenue')
                                ->label(__('dashboard.pages.reports.stats.total_revenue'))
                                ->state(number_format($stats['total_revenue']) . ' SYP')
                                ->icon('heroicon-o-currency-dollar')
                                ->iconColor('primary')
                                ->weight(FontWeight::Bold)
                                ->copyable()
                                ->copyMessage(__('Copied')),
                        ]),

                    Section::make()
                        ->schema([
                            TextEntry::make('booking_revenue')
                                ->label(__('dashboard.pages.reports.stats.booking_revenue'))
                                // fix: use the correct price approach

                                ->state(number_format($stats['booking_revenue']) . ' SYP')
                                ->icon('heroicon-o-ticket')
                                ->iconColor('success')
                                ->weight(FontWeight::Bold),
                        ]),

                    Section::make()
                        ->schema([
                            TextEntry::make('store_revenue')
                                ->label(__('dashboard.pages.reports.stats.store_revenue'))
                                ->state(number_format($stats['merchandise_revenue']) . ' SYP')
                                ->icon('heroicon-o-shopping-bag')
                                ->iconColor('warning')
                                ->weight(FontWeight::Bold),
                        ]),

                    Section::make()
                        ->schema([
                            TextEntry::make('total_bookings')
                                ->label(__('dashboard.pages.reports.stats.total_bookings'))
                                ->state(number_format($stats['total_bookings']))
                                ->icon('heroicon-o-calendar-days')
                                ->iconColor('info')
                                ->weight(FontWeight::Bold),
                        ]),

                    Section::make()
                        ->schema([
                            TextEntry::make('total_orders')
                                ->label(__('dashboard.pages.reports.stats.total_merchandise_orders'))
                                ->state(number_format($stats['total_merchandise_orders']))
                                ->icon('heroicon-o-archive-box')
                                ->iconColor('gray')
                                ->weight(FontWeight::Bold),
                        ]),
                ]),

            Grid::make(['default' => 1, 'lg' => 2])
                ->schema([

                    Section::make(__('dashboard.pages.reports.popular_classes.heading'))
                        ->icon('heroicon-o-fire')
                        ->iconColor('warning')
                        ->schema(
                            $classes->isEmpty()
                            ? [
                                TextEntry::make('no_classes')
                                    ->hiddenLabel()
                                    ->state(__('dashboard.pages.reports.popular_classes.empty', [], 'en') ?? 'No data for this period.')
                                    ->color('gray'),
                            ]
                            : $classes->values()->map(function ($class, int $i) use ($stats): TextEntry {
                                $locale = app()->getLocale();
                                $title = is_array($class->title)
                                    ? ($class->title[$locale] ?? $class->title['en'] ?? 'Unknown')
                                    : ($class->title ?? 'Unknown');

                                $pct = $stats['total_bookings'] > 0
                                    ? min(100, round(($class->total_attendance / $stats['total_bookings']) * 100))
                                    : 0;

                                return TextEntry::make('class_' . $i)
                                    ->label($title)
                                    ->state(
                                        __('dashboard.pages.reports.popular_classes.attendees', ['count' => $class->total_attendance])
                                        . '   ·   '
                                        . __('dashboard.pages.reports.popular_classes.sessions', ['count' => $class->sessions_count])
                                        . '   ·   '
                                        . __('dashboard.pages.reports.popular_classes.avg', ['count' => $class->avg_attendance])
                                    )
                                    ->badge()
                                    ->color('primary')
                                    ->tooltip($pct . '% of total bookings');
                            })->toArray()
                        ),

                    Section::make(__('dashboard.pages.reports.top_merchandise.heading'))
                        ->icon('heroicon-o-chart-bar-square')
                        ->iconColor('success')
                        ->schema(
                            $merch->isEmpty()
                            ? [
                                TextEntry::make('no_merch')
                                    ->hiddenLabel()
                                    ->state(__('dashboard.pages.reports.top_merchandise.empty', [], 'en') ?? 'No sales for this period.')
                                    ->color('gray'),
                            ]
                            : $merch->values()->map(function ($item, int $i): TextEntry {
                                $locale = app()->getLocale();
                                $name = $item->name[$locale] ?? $item->name['en'] ?? '';

                                return TextEntry::make('merch_' . $i)
                                    ->label($name)
                                    ->state(
// fix: use the correct price approach
                    
                                        number_format($item->revenue) . ' SYP'
                                        . ' · '
                                        . __('dashboard.pages.reports.top_merchandise.sold', ['count' => $item->quantity])
                                    )
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-cube');
                            })->toArray()
                        ),
                ]),
        ]);
    }

    private function stats(): array
    {
        if ($this->_stats !== null) {
            return $this->_stats;
        }

        $bookingRepo = App::make(BookingEloquentRepository::class);
        $merchandiseRepo = App::make(MerchandiseOrderEloquentRepository::class);

        [$startDate, $endDate] = $this->getPeriodDates();

        $bookingRevenue = $bookingRepo->getTotalRevenue($startDate, $endDate);
        $merchandiseRevenue = $merchandiseRepo->getTotalRevenue($startDate, $endDate);

        return $this->_stats = [
            'booking_revenue' => $bookingRevenue,
            'merchandise_revenue' => $merchandiseRevenue,
            'total_revenue' => $bookingRevenue + $merchandiseRevenue,
            'total_bookings' => $bookingRepo->getTotalCount($startDate, $endDate),
            'total_merchandise_orders' => $merchandiseRepo->getTotalCount($startDate, $endDate),
        ];
    }

    private function popularClasses(): Collection
    {
        if ($this->_classes !== null) {
            return $this->_classes;
        }

        [$startDate, $endDate] = $this->getPeriodDates();

        return $this->_classes = App::make(ClassesEloquentRepository::class)
            ->getPopularClassesSummary(5, $startDate, $endDate);
    }

    private function merchandiseSales(): Collection
    {
        if ($this->_merch !== null) {
            return $this->_merch;
        }

        [$startDate, $endDate] = $this->getPeriodDates();

        return $this->_merch = App::make(MerchandiseOrderEloquentRepository::class)
            ->getTopSellingSummary(5, $startDate, $endDate);
    }
}
