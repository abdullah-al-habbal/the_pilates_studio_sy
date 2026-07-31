<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\GetUsersListHandler;
use App\Http\Requests\Admin\Scheduler\GetUsersListRequest;
use App\Services\Log\LoggingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class GetUsersListAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetUsersListHandler $handler,
        private readonly LoggingService $logger
    ) {}

    public function __invoke(GetUsersListRequest $request): JsonResponse
    {
        try {
            $sessionId = $request->validated('session_id');
            $this->logger->info('[Scheduler:GetUsersListAction] Fetching Users List', ['session_id' => $sessionId]);
            $users = $this->handler->handle($sessionId !== null ? (int) $sessionId : null);

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
