<?php
declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Currency;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use App\Repositories\Eloquent\MerchandiseOrder\MerchandiseOrderEloquentRepository;
use App\Repositories\Eloquent\Refund\RefundEloquentRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class DailyBalanceService
{
    public function __construct(
        private readonly BookingEloquentRepository $bookingRepo,
        private readonly MerchandiseOrderEloquentRepository $orderRepo,
        private readonly ClubExpenseEloquentRepository $expenseRepo,
        private readonly RefundEloquentRepository $refundRepo,
    ) {
    }
    public function getSummary(?string $date = null): Collection
    {
        [$start, $end] = $this->resolveDateRange($date);

        $expenseTotals = $this->expenseRepo->getTotalsByCurrency($start, $end);
        $refundTotals = $this->refundRepo->getTotalsByCurrency($start, $end);

        return Currency::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (Currency $currency) use ($start, $end, $expenseTotals, $refundTotals): array {
                $pkgRevenue = $this->bookingRepo->getTotalRevenueByCurrency($currency->id, $start, $end);
                $merchRevenue = $this->orderRepo->getTotalRevenueByCurrency($currency->id, $start, $end);
                $expenses = (int) ($expenseTotals->get($currency->id)?->total ?? 0);
                $refunds = (int) ($refundTotals->get($currency->id)?->total ?? 0);
                $totalRevenue = $pkgRevenue + $merchRevenue;
                $trueBalance = $totalRevenue - $expenses - $refunds;

                return [
                    'currency_id' => $currency->id,
                    'currency_code' => $currency->code,
                    'currency_symbol' => $currency->symbol,
                    'currency_decimals' => $currency->decimal_places,
                    'package_revenue' => $pkgRevenue,
                    'merchandise_revenue' => $merchRevenue,
                    'total_revenue' => $totalRevenue,
                    'total_expenses' => $expenses,
                    'total_refunds' => $refunds,
                    'true_balance' => $trueBalance,
                ];
            });
    }

    private function resolveDateRange(?string $date): array
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();
        return [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
    }
}
