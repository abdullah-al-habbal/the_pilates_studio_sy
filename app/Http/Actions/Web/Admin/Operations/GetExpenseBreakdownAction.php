<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetExpenseBreakdownAction
{
    use ApiResponseTrait;

    public function __invoke(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $expenses = ClubExpense::where('expense_date', $date)
            ->whereIn('status', [ClubExpenseStatusEnum::PENDING, ClubExpenseStatusEnum::APPROVED])
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'category_name' => $first->category?->name ?? 'Uncategorized',
                    'total_amount'  => $group->sum('amount'),
                ];
            })
            ->values();

        return $this->success(data: $expenses->toArray());
    }
}
