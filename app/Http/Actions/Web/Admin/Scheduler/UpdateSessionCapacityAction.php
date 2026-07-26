<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Commands\Admin\Scheduler\UpdateSessionCapacityCommand;
use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\UpdateSessionCapacityHandler;
use App\Http\Requests\Admin\Scheduler\UpdateSessionCapacityRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class UpdateSessionCapacityAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UpdateSessionCapacityHandler $handler,
    ) {}

    public function __invoke(UpdateSessionCapacityRequest $request, int $sessionId): JsonResponse
    {
        try {
            $this->handler->handle(
                new UpdateSessionCapacityCommand(
                    sessionId: $sessionId,
                    capacity: (int) $request->validated('capacity'),
                    reason: $request->validated('reason'),
                )
            );

            return $this->success(
                code: SuccessCodeEnum::UPDATED,
                message: 'Session capacity updated successfully.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to update session capacity.',
            );
        }
    }
}
