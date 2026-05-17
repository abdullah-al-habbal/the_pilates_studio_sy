<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Models\Booking;
use App\Enums\BookingStatusEnum;
use Illuminate\Validation\ValidationException;

final readonly class RefundValidatorService
{
    public function assertRefundEligible(Booking $booking, ?int $requestedAmount): void
    {
        if ($booking->status !== BookingStatusEnum::ACTIVE) {
            throw ValidationException::withMessages([
                'booking' => 'Cannot refund a booking that is not active.',
            ]);
        }

        if ($booking->expires_at && $booking->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'booking' => 'Cannot refund an expired booking.',
            ]);
        }

        if ($booking->paid_amount === null) {
            throw ValidationException::withMessages([
                'booking' => 'This booking does not have a recorded payment amount.',
            ]);
        }

        if ($booking->remaining_credits <= 0) {
            throw ValidationException::withMessages([
                'booking' => 'Cannot refund a booking that has no remaining credits. Please assign a new package instead.',
            ]);
        }

        $refundAmount = $requestedAmount ?? $booking->paid_amount;

        if ($refundAmount > $booking->paid_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Refund amount cannot exceed the original paid amount.',
            ]);
        }

        if ($refundAmount < 0 || ($refundAmount === 0 && $booking->paid_amount !== 0)) {
            throw ValidationException::withMessages([
                'amount' => 'Refund amount must be positive, or zero only if the original payment was zero.',
            ]);
        }
    }
}
