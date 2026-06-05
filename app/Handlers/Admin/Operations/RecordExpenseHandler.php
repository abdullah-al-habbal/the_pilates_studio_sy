<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Commands\Admin\Operations\RecordExpenseCommand;
use App\Models\ClubExpense;
use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use App\Repositories\Eloquent\ClubExpenseCategory\ClubExpenseCategoryEloquentRepository;
use App\Enums\ClubExpenseStatusEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class RecordExpenseHandler
{
    public function __construct(
        private ClubExpenseEloquentRepository $repository,
        private ClubExpenseCategoryEloquentRepository $categoryRepository,
    ) {
    }

    public function handle(RecordExpenseCommand $command): ClubExpense
    {
        return DB::transaction(function () use ($command) {
            $category = $this->categoryRepository->firstOrCreateByName($command->categoryName);

            $data = [
                'category_id' => $category->id,
                'currency_id' => $command->currencyId,
                'amount' => $command->amount,
                'notes' => $command->notes,
                'recorded_by' => $command->recordedBy,
                'expense_date' => ($command->expenseDate ?? Carbon::today())->toDateString(),
            ];

            $recorder = User::find($command->recordedBy);

            if ($recorder?->isMainAdmin()) {
                $data['status'] = ClubExpenseStatusEnum::APPROVED;
                $data['approved_by'] = $command->recordedBy;
                $data['approved_at'] = Carbon::now();
            }

            return $this->repository->create($data);
        });
    }
}
