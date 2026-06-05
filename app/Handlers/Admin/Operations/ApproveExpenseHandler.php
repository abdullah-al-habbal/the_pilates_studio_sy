<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use Carbon\Carbon;

final readonly class ApproveExpenseHandler
{
    public function handle(int $expenseId, int $approvedBy): ClubExpense
    {
        $expense = ClubExpense::findOrFail($expenseId);

        $expense->update([
            'status' => ClubExpenseStatusEnum::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => Carbon::now(),
        ]);

        return $expense->fresh();
    }
}
