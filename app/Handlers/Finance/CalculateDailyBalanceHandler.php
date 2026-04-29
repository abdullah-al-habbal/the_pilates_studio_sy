<?php
declare(strict_types=1);
namespace App\Handlers\Finance;
use App\Models\Booking;
use App\Models\MerchandiseOrder;
use App\Models\Refund;
use Carbon\Carbon;
class CalculateDailyBalanceHandler
{
    public function __construct(
        private readonly SumClubExpensesForDateHandler $sumExpensesHandler
    ) {}
    /**
     * Calculate complete daily balance breakdown
     * Formula: net = (booking_revenue + store_revenue) - refunds - expenses
     */
    public function handle(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $bookingRevenue = Booking::whereBetween('created_at', [$start, $end])
            ->whereNotNull('paid_amount')
            ->sum('paid_amount');
        $storeRevenue = MerchandiseOrder::whereBetween('fulfilled_at', [$start, $end])
            ->whereNotNull('total_price')
            ->sum('total_price');
        $refunds = Refund::whereBetween('refunded_at', [$start, $end])->sum('amount');
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
