<?php
declare(strict_types=1);

namespace App\Repositories\Eloquent\ClubExpense;

use App\Models\ClubExpense;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ClubExpenseEloquentRepository
{

    public function getTotalsByCurrency(
        ?CarbonInterface $start = null,
        ?CarbonInterface $end = null,
    ): Collection {
        return ClubExpense::query()
            ->selectRaw('currency_id, SUM(amount) as total')
            ->when($start, fn($q) => $q->whereDate('expense_date', '>=', $start->toDateString()))
            ->when($end, fn($q) => $q->whereDate('expense_date', '<=', $end->toDateString()))
            ->groupBy('currency_id')
            ->get()
            ->keyBy('currency_id');
    }

    public function create(array $data): ClubExpense
    {
        return ClubExpense::create($data);
    }
}
