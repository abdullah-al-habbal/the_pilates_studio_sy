<?php
declare(strict_types=1);

namespace App\Repositories\Eloquent\ClubExpense;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ClubExpenseEloquentRepository
{

    public function getTotalsByCurrency(
        ?CarbonInterface $start = null,
        ?CarbonInterface $end = null,
        ?int $creatorId = null,
    ): Collection {
        return ClubExpense::query()
            ->where('status', ClubExpenseStatusEnum::APPROVED->value)
            ->selectRaw('currency_id, SUM(amount) as total')
            ->when($creatorId, fn($q) => $q->where('recorded_by', $creatorId))
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
