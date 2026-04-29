<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\UnfreezeBookingHandler;
use App\Http\Requests\Admin\Operations\UnfreezeBookingRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class UnfreezeBookingAction
{
    use ApiResponseTrait;

    public function __construct(
        private UnfreezeBookingHandler $handler
    ) {}

    /**
     * Unfreeze a booking with validated request.
     */
    public function __invoke(UnfreezeBookingRequest $request, int $bookingId): JsonResponse
    {
        try {
            $newBooking = $this->handler->handle($bookingId);

            return $this->success(
                data: $newBooking,
                message: 'Booking unfrozen and new package generated.'
            );
        } catch (\Exception $e) {
            return $this->unprocessable($e->getMessage());
        }
    }
}
