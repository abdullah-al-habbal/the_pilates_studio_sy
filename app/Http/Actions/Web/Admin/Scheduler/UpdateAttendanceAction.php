<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Commands\Admin\Scheduler\UpdateAttendanceCommand;
use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Enums\AttendanceStatusEnum;
use App\Handlers\Admin\Scheduler\UpdateAttendanceHandler;
use App\Http\Requests\Admin\Scheduler\UpdateAttendanceRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

final class UpdateAttendanceAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UpdateAttendanceHandler $handler
    ) {
    }

    public function __invoke(UpdateAttendanceRequest $request, int $sessionId, int $bookingSessionId): JsonResponse
    {
        try {
            $this->handler->handle(
                new UpdateAttendanceCommand(
                    bookingSessionId: $bookingSessionId,
                    status: AttendanceStatusEnum::from($request->validated('status')),
                )
            );

            return $this->success(
                code: SuccessCodeEnum::UPDATED,
                message: 'Attendance updated successfully.'
            );
        } catch (Throwable $e) {
            report($e);
            return $this->error(
                code: ErrorCodeEnum::INTERNAL_SERVER_ERROR,
                message: 'Failed to update attendance.'
            );
        }
    }
}