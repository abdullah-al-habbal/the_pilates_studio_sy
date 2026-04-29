<?php
declare(strict_types=1);
namespace App\Handlers\Finance;
use App\Models\ClubExpense;
use Carbon\Carbon;
class SumClubExpensesForDateHandler
{
    public function __invoke(Carbon $date): int
    {
        return ClubExpense::whereDate('expense_date', $date)->sum('amount');
    }
}
