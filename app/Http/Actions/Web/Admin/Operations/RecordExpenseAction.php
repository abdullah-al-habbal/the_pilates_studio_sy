<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Commands\Admin\Operations\RecordExpenseCommand;
use App\Handlers\Admin\Operations\RecordExpenseHandler;
use App\Http\Requests\Admin\Operations\RecordExpenseRequest;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class RecordExpenseAction
{
    use ApiResponseTrait;

    public function __construct(
        private RecordExpenseHandler $handler
    ) {
    }

    public function __invoke(RecordExpenseRequest $request): JsonResponse
    {
        try {
            $command = new RecordExpenseCommand(
                categoryName: $request->category_name,
                currencyId: (int) $request->currency_id,
                amount: (int) $request->amount,
                recordedBy: (int) auth()->id(),
                notes: $request->notes,
                expenseDate: $request->date ? Carbon::parse($request->date) : null,
            );

            $expense = $this->handler->handle(
                categoryName: $command->categoryName,
                currencyId: $command->currencyId,
                amount: $command->amount,
                recordedBy: $command->recordedBy,
                notes: $command->notes,
                expenseDate: $command->expenseDate,
            );

            return $this->created(
                data: $expense,
                message: 'Expense recorded successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - RecordExpense failed: ' . $e->getMessage(), [
                'exception' => $e,
                'category_name' => $request->category_name,
                'currency_id' => $request->currency_id,
                'amount' => $request->amount,
            ]);

            return $this->error(message: 'Failed to record expense.');
        }
    }
}
