<?php
declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Currency;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Repositories\Eloquent\Refund\RefundEloquentRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use App\Services\Finance\ExchangeRateSnapshotService;
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

    public function getSummary(?string $date = null, ?array $currencies = null, bool $convertToBase = false): Collection
    {
        [$start, $end] = $this->resolveDateRange($date);

        return $this->getSummaryForRange($start, $end, $currencies, $convertToBase);
    }

    public function getSummaryForRange(
        CarbonInterface $start,
        CarbonInterface $end,
        ?array $currencies = null,
        bool $convertToBase = false,
    ): Collection {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        $baseCurrency = $this->snapshotService->currencyService->getBaseCurrency();

        $expenseTotals = $this->expenseRepo->getTotalsByCurrency($start, $end);
        $refundTotals = $this->refundRepo->getTotalsByCurrency($start, $end);

        $query = Currency::where('is_active', true)->orderBy('id');
        if (! empty($currencies)) {
            $query->whereIn('code', $currencies);
        }

        return $query->get()->map(function (Currency $currency) use ($start, $end, $expenseTotals, $refundTotals, $convertToBase, $baseCurrency): array {
            $pkgRevenueRaw = $this->bookingRepo->getRevenueByCurrency($start, $end)
                ->firstWhere('currency_id', $currency->id)?->total_revenue ?? 0;
            $merchRevenueRaw = $this->orderRepo->getRevenueByCurrency($start, $end)
                ->firstWhere('currency_id', $currency->id)?->total_revenue ?? 0;

            $expensesRaw = (int) ($expenseTotals->get($currency->id)?->total ?? 0);
            $refundsRaw = (int) ($refundTotals->get($currency->id)?->total ?? 0);

            $totalRevenueRaw = $pkgRevenueRaw + $merchRevenueRaw;
            $trueBalanceRaw = $totalRevenueRaw - $expensesRaw - $refundsRaw;

            $result = [
                'currency_id' => $currency->id,
                'currency_code' => $currency->code,
                'currency_symbol' => $currency->symbol,
                'currency_decimals' => $currency->decimal_places,
                'package_revenue' => $pkgRevenueRaw,
                'merchandise_revenue' => $merchRevenueRaw,
                'total_revenue' => $totalRevenueRaw,
                'total_expenses' => $expensesRaw,
                'total_refunds' => $refundsRaw,
                'true_balance' => $trueBalanceRaw,
                'base_conversion_applied' => false,
                'base_currency_code' => null,
                'total_revenue_in_base' => null,
                'true_balance_in_base' => null,
            ];

            if ($convertToBase && $currency->id !== $baseCurrency->id) {
                $snapshotRate = $this->snapshotService->getHistoricalRate($currency->id, $start);

                if ($snapshotRate !== null && $snapshotRate > 0) {
                    $result['base_conversion_applied'] = true;
                    $result['base_currency_code'] = $baseCurrency->code;

                    $result['total_revenue_in_base'] = $this->snapshotService->convertToBase(
                        $totalRevenueRaw,
                        $currency->id,
                        $snapshotRate
                    );
                    $result['true_balance_in_base'] = $this->snapshotService->convertToBase(
                        $trueBalanceRaw,
                        $currency->id,
                        $snapshotRate
                    );
                }
            } elseif ($convertToBase && $currency->id === $baseCurrency->id) {
                $result['base_conversion_applied'] = true;
                $result['base_currency_code'] = $baseCurrency->code;
                $result['total_revenue_in_base'] = $totalRevenueRaw;
                $result['true_balance_in_base'] = $trueBalanceRaw;
            }

            return $result;
        });
    }

    private function resolveDateRange(?string $date): array
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();

        return [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
    }
}
