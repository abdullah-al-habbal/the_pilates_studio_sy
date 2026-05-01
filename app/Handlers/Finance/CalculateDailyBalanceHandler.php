<?php

declare(strict_types=1);

namespace App\Handlers\Finance;

use App\Models\Booking;
use App\Models\Refund;
use App\Services\Currency\CurrencyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalculateDailyBalanceHandler
{
    public function __construct(
        private readonly SumClubExpensesForDateHandler $sumExpensesHandler,
        private readonly CurrencyService $currencyService
    ) {}

    public function handle(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $currencyId = $this->currencyService->getDefaultCurrency()->id;

        $bookingRevenue = Booking::where('currency_id', $currencyId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('paid_amount')
            ->sum('paid_amount');

        $storeRevenue = Booking::query()
            ->join('merchandise_orders', 'bookings.id', '=', 'merchandise_orders.customer_id')
            ->join('center_merchandises', 'merchandise_orders.merchandise_id', '=', 'center_merchandises.id')
            ->join('prices', function ($join) use ($currencyId) {
                $join->on('center_merchandises.id', '=', 'prices.priceable_id')
                     ->where('prices.priceable_type', 'App\Models\CenterMerchandise')
                     ->where('prices.currency_id', $currencyId);
            })
            ->whereBetween('merchandise_orders.fulfilled_at', [$start, $end])
            ->sum(DB::raw('prices.amount * merchandise_orders.quantity'));

        $refunds = Refund::where('currency_id', $currencyId)
            ->whereBetween('refunded_at', [$start, $end])
            ->sum('amount');

        $expenses = ($this->sumExpensesHandler)($date);
        $netBalance = ($bookingRevenue + $storeRevenue) - $refunds - $expenses;

        return [
            'date' => $date->toDateString(),
            'booking_revenue' => $bookingRevenue,
            'store_revenue' => $storeRevenue,
            'refunds' => $refunds,
            'expenses' => $expenses,
            'net_balance' => $netBalance,
        ];
    }
}
