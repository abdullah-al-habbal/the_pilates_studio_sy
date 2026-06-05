<?php
declare(strict_types=1);

namespace App\Services\Finance;

use App\Data\Reports\CurrencySummaryData;
use App\Models\Currency;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Repositories\Eloquent\Refund\RefundEloquentRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DailyBalanceService
{
    public function __construct(
        private readonly BookingEloquentRepository $bookingRepo,
        private readonly MerchandiseOrderEloquentRepository $orderRepo,
        private readonly ClubExpenseEloquentRepository $expenseRepo,
        private readonly RefundEloquentRepository $refundRepo,
        private readonly ExchangeRateSnapshotService $snapshotService,
    ) {
    }

    /**
     * @return Collection<int, CurrencySummaryData>
     */
    public function getSummary(?string $date = null, ?array $currencies = null, bool $convertToBase = false): Collection
    {
        [$start, $end] = $this->resolveDateRange($date);

        return $this->getSummaryForRange($start, $end, $currencies, $convertToBase);
    }

    /**
     * @return Collection<int, CurrencySummaryData>
     */
    public function getSummaryForRange(
        CarbonInterface $start,
        CarbonInterface $end,
        ?array $currencies = null,
        bool $convertToBase = false,
    ): Collection {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        $baseCurrency = $this->snapshotService->currencyService->getBaseCurrency();

        $creatorId = $this->resolveCreatorScope();

        $expenseTotals = $this->expenseRepo->getTotalsByCurrency($start, $end, $creatorId);
        $refundTotals = $this->refundRepo->getTotalsByCurrency($start, $end);

        $query = Currency::where('is_active', true)->orderBy('id');
        if (! empty($currencies)) {
            $query->whereIn('code', $currencies);
        }

        return $query->get()->map(function (Currency $currency) use (
            $start,
            $end,
            $expenseTotals,
            $refundTotals,
            $convertToBase,
            $baseCurrency,
            $creatorId,
        ): CurrencySummaryData {
            $pkgRevenueRaw = $this->bookingRepo->getRevenueByCurrency($start, $end, $creatorId)
                ->firstWhere('currency_id', $currency->id)?->total_revenue ?? 0;
            $merchRevenueRaw = $this->orderRepo->getRevenueByCurrency($start, $end, $creatorId)
                ->firstWhere('currency_id', $currency->id)?->total_revenue ?? 0;

            $expensesRaw = (int) ($expenseTotals->get($currency->id)?->total ?? 0);
            $refundsRaw = (int) ($refundTotals->get($currency->id)?->total ?? 0);

            $totalRevenueRaw = $pkgRevenueRaw + $merchRevenueRaw;
            $trueBalanceRaw = $totalRevenueRaw - $expensesRaw - $refundsRaw;

            $baseConversionApplied = false;
            $baseCurrencyCode = null;
            $totalRevenueInBase = null;
            $trueBalanceInBase = null;

            if ($convertToBase && $currency->id !== $baseCurrency->id) {
                $snapshotRate = $this->snapshotService->getHistoricalRate($currency->id, $start);

                if ($snapshotRate !== null && $snapshotRate > 0) {
                    $baseConversionApplied = true;
                    $baseCurrencyCode = $baseCurrency->code;

                    $totalRevenueInBase = $this->snapshotService->convertToBase(
                        $totalRevenueRaw,
                        $currency->id,
                        $snapshotRate
                    );
                    $trueBalanceInBase = $this->snapshotService->convertToBase(
                        $trueBalanceRaw,
                        $currency->id,
                        $snapshotRate
                    );
                }
            } elseif ($convertToBase && $currency->id === $baseCurrency->id) {
                $baseConversionApplied = true;
                $baseCurrencyCode = $baseCurrency->code;
                $totalRevenueInBase = $totalRevenueRaw;
                $trueBalanceInBase = $trueBalanceRaw;
            }

            return new CurrencySummaryData(
                currencyId: $currency->id,
                currencyCode: $currency->code,
                currencySymbol: $currency->symbol,
                currencyDecimals: $currency->decimal_places,
                packageRevenue: $pkgRevenueRaw,
                merchandiseRevenue: $merchRevenueRaw,
                totalRevenue: $totalRevenueRaw,
                totalExpenses: $expensesRaw,
                totalRefunds: $refundsRaw,
                trueBalance: $trueBalanceRaw,
                baseConversionApplied: $baseConversionApplied,
                baseCurrencyCode: $baseCurrencyCode,
                totalRevenueInBase: $totalRevenueInBase,
                trueBalanceInBase: $trueBalanceInBase,
            );
        });
    }

    private function resolveCreatorScope(): ?int
    {
        $user = auth()->user();
        if (!$user || $user->isMainAdmin()) {
            return null;
        }
        return $user->id;
    }

    private function resolveDateRange(?string $date): array
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();

        return [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
    }
}
