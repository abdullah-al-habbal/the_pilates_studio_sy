<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

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
            $expense = $this->handler->handle(
                $request->category_name,
                (int) $request->amount,
                (int) auth()->id(),
                $request->notes,
                $request->date ? Carbon::parse($request->date) : null
            );

            return $this->created(
                data: $expense,
                message: 'Expense recorded successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - RecordExpense failed: ' . $e->getMessage(), [
                'exception' => $e,
                'category' => $request->category_name,
                'amount' => $request->amount,
            ]);

            return $this->error(message: 'Failed to record expense.');
        }
    }
}
