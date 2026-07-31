<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetExpenseBreakdownHandler;
use App\Http\Requests\Admin\Operations\GetExpenseBreakdownRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class GetExpenseBreakdownAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetExpenseBreakdownHandler $handler
    ) {}

    public function __invoke(GetExpenseBreakdownRequest $request): JsonResponse
    {
        $expenses = $this->handler->handle($request->getDate());

        return $this->success(data: $expenses->toArray());
    }
}
