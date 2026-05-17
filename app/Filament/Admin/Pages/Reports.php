<?php
// filePath: app\Filament\Admin\Pages\Reports.php
declare(strict_types=1);


namespace App\Filament\Admin\Pages;

use App\Models\Currency;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\Classes\ClassesEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Services\Currency\CurrencyService;
use App\Services\Finance\DailyBalanceService;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Toggle;
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
use App\Data\Reports\CurrencySummaryData;

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

    public bool $convertToBase = false;

    public array $selectedCurrencies = [];

    private ?array $_stats = null;
    private ?Collection $_classes = null;
    private ?Collection $_merch = null;

    public function mount(): void
    {
        $this->dailyDate = now()->toDateString();
        $this->month = now()->format('Y-m');
        $this->year = now()->format('Y');

        $this->selectedCurrencies = Currency::where('is_active', true)->pluck('code')->toArray();
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
            $service = app(DailyBalanceService::class);
            $currency = app(CurrencyService::class)->getDefaultCurrency();
            $summary = $service->getSummary(now()->toDateString(), [$currency->code]);
            $item = $summary->firstWhere('currency_code', $currency->code);
            if (!$item)
                return '—';

            $divisor = 10 ** $currency->decimal_places;
            $amount = $item['base_conversion_applied'] && $item['total_revenue_in_base'] !== null
                ? $item['total_revenue_in_base']
                : $item['total_revenue'];

            return number_format($amount / $divisor, $currency->decimal_places) . ' ' . $currency->symbol;
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

    private function buildSummaryData(): Collection
    {
        $summary = app(DailyBalanceService::class)->getSummary(
            date: $this->period === 'custom'
            ? null
            : ($this->dailyDate ?: now()->toDateString()),
            currencies: $this->selectedCurrencies ?: null,
            convertToBase: $this->convertToBase,
        );

        return collect($summary)
            ->map(
                fn(array $item): CurrencySummaryData =>
                CurrencySummaryData::fromArray($item)
            );
    }

    private function formatCurrency(
        int $amount,
        string $symbol,
        int $decimals,
    ): string {
        return number_format(
            $amount / (10 ** $decimals),
            $decimals
        ) . ' ' . $symbol;
    }

    private function buildCurrencySections(
        Collection $summary,
        Currency $baseCurrency,
        bool $hasBaseConversion,
    ): array {
        return $summary->flatMap(function (CurrencySummaryData $currency) use ($baseCurrency, $hasBaseConversion): array {
            $fmt = fn(int $v): string => $this->formatCurrency(
                amount: $v,
                symbol: $currency->currencySymbol,
                decimals: $currency->currencyDecimals,
            );

            $fmtBase = fn(?int $v): string => $v !== null
                ? $this->formatCurrency(
                    amount: $v,
                    symbol: $baseCurrency->symbol,
                    decimals: $baseCurrency->decimal_places,
                )
                : '—';

            $sections = [];

            $sections[] = Section::make("{$currency->currencyCode} — Financial Summary")
                ->icon('heroicon-o-banknotes')
                ->iconColor('primary')
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            TextEntry::make("revenue_{$currency->currencyId}")
                                ->label("Total Revenue ({$currency->currencyCode})")
                                ->state($fmt($currency->totalRevenue))
                                ->icon('heroicon-o-currency-dollar')
                                ->iconColor('primary')
                                ->weight(FontWeight::Bold),
                            TextEntry::make("packages_{$currency->currencyId}")
                                ->label("Package Revenue")
                                ->state($fmt($currency->packageRevenue))
                                ->icon('heroicon-o-ticket')
                                ->iconColor('success'),
                            TextEntry::make("merch_{$currency->currencyId}")
                                ->label("Merchandise Revenue")
                                ->state($fmt($currency->merchandiseRevenue))
                                ->icon('heroicon-o-shopping-bag')
                                ->iconColor('warning'),
                            TextEntry::make("expenses_{$currency->currencyId}")
                                ->label("Expenses")
                                ->state($fmt($currency->totalExpenses))
                                ->icon('heroicon-o-arrow-trending-down')
                                ->iconColor('rose'),
                            TextEntry::make("refunds_{$currency->currencyId}")
                                ->label("Refunds")
                                ->state($fmt($currency->totalRefunds))
                                ->icon('heroicon-o-receipt-refund')
                                ->iconColor('amber'),
                            TextEntry::make("balance_{$currency->currencyId}")
                                ->label("True Balance")
                                ->state($fmt($currency->trueBalance))
                                ->icon('heroicon-o-calculator')
                                ->iconColor($currency->trueBalance >= 0 ? 'emerald' : 'rose')
                                ->weight(FontWeight::Black),
                        ]),
                ]);

            if ($hasBaseConversion && $currency->baseConversionApplied && $currency->currencyCode !== $baseCurrency->code) {
                $sections[] = Section::make("→ Converted to {$baseCurrency->code}")
                    ->icon('heroicon-o-arrow-path')
                    ->iconColor('gray')
                    ->collapsed()
                    ->description("Using historical exchange rate at transaction time")
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextEntry::make("revenue_base_{$currency->currencyId}")
                                    ->label("Total Revenue in {$baseCurrency->code}")
                                    ->state($fmtBase($currency->totalRevenueInBase))
                                    ->icon('heroicon-o-currency-dollar')
                                    ->iconColor('primary'),
                                TextEntry::make("balance_base_{$currency->currencyId}")
                                    ->label("True Balance in {$baseCurrency->code}")
                                    ->state($fmtBase($currency->trueBalanceInBase))
                                    ->icon('heroicon-o-calculator')
                                    ->iconColor($currency->trueBalanceInBase >= 0 ? 'emerald' : 'rose'),
                            ]),
                    ]);
            }

            return $sections;
        })->values()->all();
    }

    private function buildCurrencyFilterSection(): Section
    {
        return Section::make('Filter by Currency')
            ->icon('heroicon-o-funnel')
            ->schema([
                Grid::make(['default' => 2, 'sm' => 3, 'md' => 4])
                    ->schema(
                        Currency::where('is_active', true)->get()->map(
                            fn(Currency $curr) =>
                            Checkbox::make("currency_{$curr->id}")
                                ->label("{$curr->code} {$curr->symbol}")
                                ->default(in_array($curr->code, $this->selectedCurrencies))
                                ->live()
                                ->afterStateUpdated(fn($state) => $this->selectedCurrencies = array_filter(
                                    $this->selectedCurrencies,
                                    fn($c) => $c !== $curr->code
                                ) + ($state ? [$curr->code] : []))
                        )->toArray()
                    ),
            ]);
    }

    private function buildDisplayOptionsSection(Currency $baseCurrency): Section
    {
        return Section::make('Display Options')
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Toggle::make('convertToBase')
                    ->label("Show amounts converted to {$baseCurrency->code}")
                    ->helperText("Uses historical exchange rates from transaction snapshots")
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->convertToBase = $state),
            ]);
    }

    private function buildStatsGrid(): Grid
    {
        $stats = $this->stats();

        return Grid::make(['default' => 1, 'sm' => 2, 'lg' => 2, 'xl' => 2])
            ->schema([
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
            ]);
    }

    private function buildAnalyticsGrid(): Grid
    {
        $classes = $this->popularClasses();
        $merch = $this->merchandiseSales();
        $stats = $this->stats();
        $currency = app(CurrencyService::class)->getDefaultCurrency();
        $divisor = 10 ** $currency->decimal_places;

        return Grid::make(['default' => 1, 'lg' => 2])
            ->schema([
                Section::make(__('dashboard.pages.reports.popular_classes.heading'))
                    ->icon('heroicon-o-fire')->iconColor('warning')
                    ->schema(
                        $classes->isEmpty()
                        ? [TextEntry::make('no_classes')->hiddenLabel()->state('No data for this period.')->color('gray')]
                        : $classes->values()->map(function ($class, int $i) use ($stats): TextEntry {
                            $locale = app()->getLocale();
                            $title = is_array($class->title)
                                ? ($class->title[$locale] ?? $class->title['en'])
                                : ($class->title);
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
                            $name = is_array($item->name)
                                ? ($item->name[$locale] ?? $item->name['en'] ?? '')
                                : ($item->name ?? '');
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
            ]);
    }

    public function reportsInfolist(Schema $schema): Schema
    {
        $summary = $this->buildSummaryData();
        $baseCurrency = app(CurrencyService::class)->getBaseCurrency();

        $hasBaseConversion = $summary->contains(
            fn(CurrencySummaryData $item): bool => $item->baseConversionApplied
        );

        return $schema->components(array_merge(
            [
                $hasBaseConversion
                ? Section::make('⚠️ Base Currency Conversion Notice')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('amber')
                    ->content("All amounts marked 'Converted to {$baseCurrency->code}' use historical exchange rates captured at the time of each transaction. This ensures audit accuracy even if current rates change.")
                    ->collapsible()
                    ->collapsed()
                : null,

                $this->buildCurrencyFilterSection(),

                $this->buildDisplayOptionsSection($baseCurrency),
            ],
            $this->buildCurrencySections($summary, $baseCurrency, $hasBaseConversion),
            [
                $this->buildStatsGrid(),

                $this->buildAnalyticsGrid(),
            ]
        ));
    }

    private function stats(): array
    {
        if ($this->_stats !== null)
            return $this->_stats;

        [$start, $end] = $this->getPeriodDates();

        return $this->_stats = [
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
