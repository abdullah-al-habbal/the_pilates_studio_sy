<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Handlers\Admin\Scheduler\GetSessionsDaysInMonthHandler;
use App\Http\Requests\Admin\Scheduler\GetSessionsDaysInMonthRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class GetSessionsDaysInMonthAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetSessionsDaysInMonthHandler $handler
    ) {}

    public function __invoke(GetSessionsDaysInMonthRequest $request): JsonResponse
    {
        $dates = $this->handler->handle(
            year: $request->getYear(),
            month: $request->getMonth(),
        );

        return $this->success($dates, message: 'Session dates retrieved.');
    }
}
