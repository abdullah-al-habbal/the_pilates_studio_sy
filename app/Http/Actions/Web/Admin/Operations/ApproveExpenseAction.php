<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\ApproveExpenseHandler;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final readonly class ApproveExpenseAction
{
    use ApiResponseTrait;

    public function __construct(
        private ApproveExpenseHandler $handler,
    ) {}

    public function __invoke(int $expense): JsonResponse
    {
        if (!Auth::user()?->isMainAdmin()) {
            return $this->forbidden('Only main admin can approve expenses.');
        }

        try {
            $expense = $this->handler->handle($expense, (int) Auth::id());

            return $this->success(
                data: ['id' => $expense->id, 'status' => $expense->status->value],
                message: 'Expense approved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - ApproveExpense failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->error(message: 'Failed to approve expense.');
        }
    }
}
