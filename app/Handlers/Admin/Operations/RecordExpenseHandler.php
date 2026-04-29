<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\ClubExpense;
use App\Services\Finance\ClubExpenseService;
use DateTimeInterface;

final readonly class RecordExpenseHandler
{
    public function __construct(
        private ClubExpenseService $expenseService
    ) {}

    /**
     * Record a club expense.
     */
    public function handle(
        string $categoryName,
        int $amount,
        int $recordedBy,
        ?string $notes = null,
        ?DateTimeInterface $date = null
    ): ClubExpense {
        return $this->expenseService->record($categoryName, $amount, $recordedBy, $notes, $date);
    }
}
