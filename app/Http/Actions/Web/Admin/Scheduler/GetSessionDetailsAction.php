<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\GetSessionDetailsHandler;
use App\Queries\Admin\Scheduler\GetSessionDetailsQuery;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class GetSessionDetailsAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetSessionDetailsHandler $handler
    ) {
    }

    public function __invoke(int $sessionId): JsonResponse
    {
        try {
            
            $result = $this->handler->handle(
                new GetSessionDetailsQuery(sessionId: $sessionId)
            );

            return $this->success(data: $result, code: SuccessCodeEnum::SUCCESS);
        } catch (Throwable $e) {
            report($e);
            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to retrieve session details.'
            );
        }
    }
}