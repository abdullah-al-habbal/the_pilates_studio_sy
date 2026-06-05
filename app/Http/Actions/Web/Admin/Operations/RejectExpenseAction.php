<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\RejectExpenseHandler;
use App\Http\Requests\Admin\Operations\RejectExpenseRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final readonly class RejectExpenseAction
{
    use ApiResponseTrait;

    public function __construct(
        private RejectExpenseHandler $handler,
    ) {}

    public function __invoke(RejectExpenseRequest $request, int $expense): JsonResponse
    {
        if (!Auth::user()?->isMainAdmin()) {
            return $this->forbidden('Only main admin can reject expenses.');
        }

        try {
            $expense = $this->handler->handle(
                expenseId: $expense,
                rejectedBy: (int) Auth::id(),
                reason: $request->rejection_reason,
            );

            return $this->success(
                data: ['id' => $expense->id, 'status' => $expense->status->value],
                message: 'Expense rejected successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - RejectExpense failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->error(message: 'Failed to reject expense.');
        }
    }
}
