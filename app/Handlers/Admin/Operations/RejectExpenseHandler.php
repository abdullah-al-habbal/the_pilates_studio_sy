<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use Carbon\Carbon;

final readonly class RejectExpenseHandler
{
    public function handle(int $expenseId, int $rejectedBy, string $reason): ClubExpense
    {
        $expense = ClubExpense::findOrFail($expenseId);

        $expense->update([
            'status' => ClubExpenseStatusEnum::REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);

        return $expense->fresh();
    }
}
