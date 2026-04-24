<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\GetUsersListHandler;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class GetUsersListAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetUsersListHandler $handler
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $users = $this->handler->handle();

            return $this->success(data: $users, code: SuccessCodeEnum::SUCCESS);
        } catch (Throwable $e) {
            report($e);
            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to retrieve users list.'
            );
        }
    }
}