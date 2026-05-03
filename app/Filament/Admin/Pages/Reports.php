<?php
declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\Currency;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Repositories\Eloquent\Refund\RefundEloquentRepository;
use App\Services\Currency\CurrencyService;
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

class Reports extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected string $view = 'filament.admin.pages.reports';
    protected static ?int $navigationSort = 2;

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

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('filament.reports.today_revenue', now()->addMinutes(5), function () {
            $repo = app(MerchandiseOrderEloquentRepository::class);
            $currency = app(CurrencyService::class)->getDefaultCurrency();
            $divisor = 10 ** $currency->decimal_places;
            $total = $repo->getTotalRevenue(now()->startOfDay(), now()->endOfDay());
            return number_format($total / $divisor, $currency->decimal_places) . ' ' . $currency->symbol;
        });
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public function getPeriodDates(): array
    {
        return match ($this->period) {
            'daily' => [Carbon::parse($this->dailyDate)->startOfDay(), Carbon::parse($this->dailyDate)->endOfDay()],
            'monthly' => [Carbon::parse($this->month . '-01')->startOfMonth(), Carbon::parse($this->month . '-01')->endOfMonth()],
            'yearly' => [Carbon::create((int) $this->year)->startOfYear(), Carbon::create((int) $this->year)->endOfYear()],
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
        $currency = $this->currencyService->getDefaultCurrency();
        $divisor = 10 ** $currency->decimal_places;

        [$start, $end] = $this->getPeriodDates();
        $expenseTotals = app(ClubExpenseEloquentRepository::class)->getTotalsByCurrency($start, $end);
        $refundTotals = app(RefundEloquentRepository::class)->getTotalsByCurrency($start, $end);
        $currencies = Currency::where('is_active', true)->orderBy('id')->get();

        $currencyComponents = $currencies->flatMap(function (Currency $curr) use ($start, $end, $expenseTotals, $refundTotals): array {
            $d = 10 ** $curr->decimal_places;
            $expenses = (int) ($expenseTotals->get($curr->id)?->total ?? 0);
            $refunds = (int) ($refundTotals->get($curr->id)?->total ?? 0);

            if ($expenses === 0 && $refunds === 0) {
                return [];
            }

            $fmt = fn(int $v): string => number_format($v / $d, $curr->decimal_places) . ' ' . $curr->symbol;

            return [
                Section::make("{$curr->code} — Expenses & Refunds")
                    ->icon('heroicon-o-banknotes')
                    ->iconColor('rose')
                    ->collapsed()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextEntry::make("expenses_{$curr->id}")
                                    ->label("Total Expenses ({$curr->code})")
                                    ->state($fmt($expenses))
                                    ->icon('heroicon-o-arrow-trending-down')
                                    ->iconColor('rose')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make("refunds_{$curr->id}")
                                    ->label("Total Refunds ({$curr->code})")
                                    ->state($fmt($refunds))
                                    ->icon('heroicon-o-receipt-refund')
                                    ->iconColor('amber')
                                    ->weight(FontWeight::Bold),
                            ]),
                    ]),
            ];
        })->values()->all();

        return $schema->components(array_merge(
            [
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 5])
                    ->schema([
                        Section::make()->schema([
                            TextEntry::make('total_revenue')
                                ->label(__('dashboard.pages.reports.stats.total_revenue'))
                                ->state(number_format($stats['total_revenue'] / $divisor, $currency->decimal_places) . ' ' . $currency->symbol)
                                ->icon('heroicon-o-currency-dollar')->iconColor('primary')
                                ->weight(FontWeight::Bold)->copyable(),
                        ]),
                        Section::make()->schema([
                            TextEntry::make('booking_revenue')
                                ->label(__('dashboard.pages.reports.stats.booking_revenue'))
                                ->state(number_format($stats['booking_revenue'] / $divisor, $currency->decimal_places) . ' ' . $currency->symbol)
                                ->icon('heroicon-o-ticket')->iconColor('success')
                                ->weight(FontWeight::Bold),
                        ]),
                        Section::make()->schema([
                            TextEntry::make('store_revenue')
                                ->label(__('dashboard.pages.reports.stats.store_revenue'))
                                ->state(number_format($stats['merchandise_revenue'] / $divisor, $currency->decimal_places) . ' ' . $currency->symbol)
                                ->icon('heroicon-o-shopping-bag')->iconColor('warning')
                                ->weight(FontWeight::Bold),
                        ]),
                        Section::make()->schema([
                            TextEntry::make('total_bookings')
                                ->label(__('dashboard.pages.reports.stats.total_bookings'))
                                ->state(number_format($stats['total_bookings']))
                                ->icon('heroicon-o-calendar-days')->iconColor('info')
                                ->weight(FontWeight::Bold),
                        ]),
                        Section::make()->schema([
                            TextEntry::make('total_orders')
                                ->label(__('dashboard.pages.reports.stats.total_merchandise_orders'))
                                ->state(number_format($stats['total_merchandise_orders']))
                                ->icon('heroicon-o-archive-box')->iconColor('gray')
                                ->weight(FontWeight::Bold),
                        ]),
                    ]),

                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make(__('dashboard.pages.reports.popular_classes.heading'))
                            ->icon('heroicon-o-fire')->iconColor('warning')
                            ->schema(
                                $classes->isEmpty()
                                ? [TextEntry::make('no_classes')->hiddenLabel()->state('No data for this period.')->color('gray')]
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
                                        ->badge()->color('primary')
                                        ->tooltip($pct . '% of total bookings');
                                })->toArray()
                            ),

                        Section::make(__('dashboard.pages.reports.top_merchandise.heading'))
                            ->icon('heroicon-o-chart-bar-square')->iconColor('success')
                            ->schema(
                                $merch->isEmpty()
                                ? [TextEntry::make('no_merch')->hiddenLabel()->state('No sales for this period.')->color('gray')]
                                : $merch->values()->map(function ($item, int $i) use ($currency, $divisor): TextEntry {
                                    $locale = app()->getLocale();
                                    $name = $item->name[$locale] ?? $item->name['en'] ?? '';
                                    return TextEntry::make('merch_' . $i)
                                        ->label($name)
                                        ->state(
                                            number_format($item->revenue / $divisor, $currency->decimal_places) . ' ' . $currency->symbol
                                            . ' · '
                                            . __('dashboard.pages.reports.top_merchandise.sold', ['count' => $item->quantity])
                                        )
                                        ->badge()->color('success')->icon('heroicon-o-cube');
                                })->toArray()
                            ),
                    ]),
            ],
            $currencyComponents,
        ));
    }

    // ── Private data methods ──────────────────────────────────────────────

    private function stats(): array
    {
        if ($this->_stats !== null)
            return $this->_stats;

        [$start, $end] = $this->getPeriodDates();
        $bookingRevenue = app(BookingEloquentRepository::class)->getTotalRevenue($start, $end);
        $merchandiseRevenue = app(MerchandiseOrderEloquentRepository::class)->getTotalRevenue($start, $end);

        return $this->_stats = [
            'booking_revenue' => $bookingRevenue,
            'merchandise_revenue' => $merchandiseRevenue,
            'total_revenue' => $bookingRevenue + $merchandiseRevenue,
            'total_bookings' => app(BookingEloquentRepository::class)->getTotalCount($start, $end),
            'total_merchandise_orders' => app(MerchandiseOrderEloquentRepository::class)->getTotalCount($start, $end),
        ];
    }

    private function popularClasses(): Collection
    {
        if ($this->_classes !== null)
            return $this->_classes;
        [$start, $end] = $this->getPeriodDates();
        return $this->_classes = app(ClassesEloquentRepository::class)->getPopularClassesSummary(5, $start, $end);
    }

    private function merchandiseSales(): Collection
    {
        if ($this->_merch !== null)
            return $this->_merch;
        [$start, $end] = $this->getPeriodDates();
        return $this->_merch = app(MerchandiseOrderEloquentRepository::class)->getTopSellingSummary(5, $start, $end);
    }
}
