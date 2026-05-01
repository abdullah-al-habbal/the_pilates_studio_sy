<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\UnfreezeBookingHandler;
use App\Http\Requests\Admin\Operations\UnfreezeBookingRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class UnfreezeBookingAction
{
    use ApiResponseTrait;

    public function __construct(
        private UnfreezeBookingHandler $handler
    ) {
    }

    public function __invoke(UnfreezeBookingRequest $request, int $bookingId): JsonResponse
    {
        try {
            $newBooking = $this->handler->handle($bookingId);

            return $this->success(
                data: $newBooking,
                message: 'Booking unfrozen and new package generated.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - UnfreezeBooking failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
            ]);
            return $this->unprocessable($e->getMessage());
        }
    }
}
