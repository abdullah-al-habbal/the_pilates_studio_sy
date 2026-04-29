<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\Merchandise\MerchandiseOrderEloquentRepository;
use App\Models\ClubExpense;
use App\Models\Refund;
use Carbon\CarbonInterface;

class DailyBalanceService
{
    public function __construct(
        private readonly BookingEloquentRepository $bookingRepo,
        private readonly MerchandiseOrderEloquentRepository $merchandiseRepo,
    ) {}

    /**
     * Compute the daily balance: (Booking Revenue + Store Revenue) - (Refunds + Expenses).
     */
    public function compute(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfDay();
        $end   = $date->copy()->endOfDay();

        $bookingRevenue  = $this->bookingRepo->getTotalRevenue($start, $end);
        $storeRevenue    = $this->merchandiseRepo->getTotalRevenue($start, $end);
        $totalRevenue    = $bookingRevenue + $storeRevenue;

        $totalRefunds    = (int) Refund::whereDate('refunded_at', $date)->sum('amount');
        $totalExpenses   = (int) ClubExpense::whereDate('expense_date', $date)->sum('amount');

        $trueBalance = $totalRevenue - $totalRefunds - $totalExpenses;

        return [
            'booking_revenue'  => $bookingRevenue,
            'store_revenue'    => $storeRevenue,
            'total_revenue'    => $totalRevenue,
            'total_refunds'    => $totalRefunds,
            'total_expenses'   => $totalExpenses,
            'true_balance'     => $trueBalance,
            'date'             => $date->toDateString(),
        ];
    }
}
