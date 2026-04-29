<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\FreezeBookingHandler;
use App\Http\Requests\Admin\Operations\FreezeBookingRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class FreezeBookingAction
{
    use ApiResponseTrait;

    public function __construct(
        private FreezeBookingHandler $handler
    ) {}

    /**
     * Freeze a booking with validated request.
     */
    public function __invoke(FreezeBookingRequest $request, int $bookingId): JsonResponse
    {
        try {
            $this->handler->handle($bookingId);

            return $this->success(
                message: 'Booking frozen successfully.'
            );
        } catch (\Exception $e) {
            return $this->unprocessable($e->getMessage());
        }
    }
}
