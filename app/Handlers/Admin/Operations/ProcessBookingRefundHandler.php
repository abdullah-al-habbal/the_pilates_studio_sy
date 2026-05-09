<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ProcessBookingRefundHandler
{
    public function handle(int $bookingId, ?int $amount): Refund
    {
        return DB::transaction(function () use ($bookingId, $amount): Refund {
            $booking = Booking::lockForUpdate()->findOrFail($bookingId);

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

            $refundAmount = $amount ?? $booking->paid_amount;

            if ($refundAmount > $booking->paid_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Refund amount cannot exceed the paid amount.',
                ]);
            }

            $refund = Refund::create([
                'refundable_type' => Booking::class,
                'refundable_id' => $booking->id,
                'user_id' => $booking->user_id,
                'currency_id' => $booking->currency_id,
                'amount' => $refundAmount,
                'refunded_by' => auth()->id(),
                'refunded_at' => now(),
            ]);

            $booking->update([
                'status' => BookingStatusEnum::CANCELLED,
                'remaining_credits' => 0,
            ]);

            return $refund;
        });
    }
}
