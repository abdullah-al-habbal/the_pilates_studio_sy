<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\ProcessBookingRefundHandler;
use App\Http\Requests\Admin\Operations\ProcessBookingRefundRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final readonly class ProcessBookingRefundAction
{
    use ApiResponseTrait;

    public function __construct(
        private ProcessBookingRefundHandler $handler
    ) {
    }

    public function __invoke(ProcessBookingRefundRequest $request, int $bookingId): JsonResponse
    {
        if (!Auth::user()?->isMainAdmin()) {
            return $this->forbidden('Only main admin can process refunds.');
        }

        try {
            $amount = $request->has('amount') ? (int) $request->amount : null;

            $refund = $this->handler->handle($bookingId, $amount);

            return $this->success(
                data: $refund,
                message: 'Refund processed successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - ProcessBookingRefund failed: ' . $e->getMessage(), [
                'exception' => $e,
                'booking_id' => $bookingId,
            ]);
            return $this->unprocessable($e->getMessage());
        }
    }
}
