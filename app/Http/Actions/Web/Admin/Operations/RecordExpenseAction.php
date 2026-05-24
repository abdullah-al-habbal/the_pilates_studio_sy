<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\RecordExpenseHandler;
use App\Http\Requests\Admin\Operations\RecordExpenseRequest;
use App\Services\Log\LoggingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class RecordExpenseAction
{
    use ApiResponseTrait;

    public function __construct(
        private RecordExpenseHandler $handler,
        private LoggingService $logger,
    ) {
    }

    public function __invoke(RecordExpenseRequest $request): JsonResponse
    {
        try {
            $expense = $this->handler->handle($request->toCommand());

            return $this->created(
                data: $expense,
                message: 'Expense recorded successfully.'
            );
        } catch (\Throwable $e) {
            $this->logger->error('Operations - RecordExpense failed: ' . $e->getMessage(), [
                'exception' => $e,
                'category_name' => $request->category_name,
                'currency_id' => $request->currency_id,
                'amount' => $request->amount,
            ]);

            return $this->error(message: 'Failed to record expense.');
        }
    }
}
