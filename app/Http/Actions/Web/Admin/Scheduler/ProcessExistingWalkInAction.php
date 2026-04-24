<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Commands\Admin\Scheduler\ProcessExistingWalkInCommand;
use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\ProcessExistingWalkInHandler;
use App\Http\Requests\Admin\Scheduler\ProcessExistingWalkInRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProcessExistingWalkInAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProcessExistingWalkInHandler $handler
    ) {
    }

    public function __invoke(ProcessExistingWalkInRequest $request, int $sessionId): JsonResponse
    {
        try {
            $result = $this->handler->handle(
                new ProcessExistingWalkInCommand(
                    sessionId: $sessionId,
                    userIds: $request->validated('user_ids'),
                )
            );

            if ($result['added'] > 0) {
                return $this->success(
                    data: [
                        'added' => $result['added'],
                        'messages' => $result['messages'],
                        'errors' => $result['errors'],
                    ],
                    code: SuccessCodeEnum::SUCCESS,
                    message: $result['messages'][0] ?? 'Users added successfully.'
                );
            }

            return $this->unprocessable(
                message: $result['messages'][0] ?? 'No users could be added.',
                errors: $result['errors']
            );
        } catch (Throwable $e) {
            report($e);
            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to process existing walk‑in.'
            );
        }
    }
}