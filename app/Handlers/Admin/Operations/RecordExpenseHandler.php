<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\ClubExpense;
use App\Models\ClubExpenseCategory;
use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final readonly class RecordExpenseHandler
{
    public function __construct(
        private ClubExpenseEloquentRepository $repository,
    ) {
    }

    public function handle(
        string $categoryName,
        int $currencyId,
        int $amount,
        int $recordedBy,
        ?string $notes,
        ?CarbonInterface $expenseDate,
    ): ClubExpense {
        $category = ClubExpenseCategory::firstOrCreate(
            ['name' => $categoryName],
            ['name' => $categoryName],
        );

        return $this->repository->create([
            'category_id' => $category->id,
            'category_label' => $categoryName,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'notes' => $notes,
            'recorded_by' => $recordedBy,
            'expense_date' => ($expenseDate ?? Carbon::today())->toDateString(),
        ]);
    }
}
