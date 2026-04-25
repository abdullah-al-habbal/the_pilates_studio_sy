<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\ProcessNewWalkInHandler;
use App\Http\Requests\Admin\Scheduler\ProcessNewWalkInRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProcessNewWalkInAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProcessNewWalkInHandler $handler
    ) {
    }

    public function __invoke(ProcessNewWalkInRequest $request, int $sessionId): JsonResponse
    {
        try {
            $this->handler->handle($request->toCommand($sessionId));

            return $this->created(
                code: SuccessCodeEnum::CREATED,
                message: 'New walk‑in user added successfully.'
            );
        } catch (Throwable $e) {
            report($e);
            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to process new walk‑in.'
            );
        }
    }
}