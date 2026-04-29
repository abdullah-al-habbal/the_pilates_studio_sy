<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\RecordExpenseHandler;
use App\Http\Requests\Admin\Operations\RecordExpenseRequest;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

final readonly class RecordExpenseAction
{
    use ApiResponseTrait;

    public function __construct(
        private RecordExpenseHandler $handler
    ) {}

    /**
     * Record a new expense with validated data.
     */
    public function __invoke(RecordExpenseRequest $request): JsonResponse
    {
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
    }
}
