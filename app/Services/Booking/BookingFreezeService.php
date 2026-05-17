<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingSourceTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingFreezeService
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    /**
     * Freeze an active booking.
     * Marks the booking as FROZEN, records frozen_at.
     */
    public function freeze(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            if ($booking->status !== BookingStatusEnum::ACTIVE) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Only active bookings can be frozen.',
                ]);
            }

            if ($booking->remaining_credits <= 0) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Cannot freeze a package with no remaining credits. Assign a new package first.',
                ]);
            }

            $booking->update([
                'status'      => BookingStatusEnum::FROZEN,
                'frozen_at'   => now(),
                'source_type' => BookingSourceTypeEnum::FREEZE_ORIGIN,
            ]);
        });
    }

    /**
     * Unfreeze a booking.
     * Calculates remaining validity days, creates a new FREEZE_RESUME booking,
     * leaves the original as historical record.
     */
    public function unfreeze(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            if ($booking->status !== BookingStatusEnum::FROZEN) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Booking is not currently frozen.',
                ]);
            }

            $originalExpiry  = $booking->expires_at;
            $frozenAt        = $booking->frozen_at ?? now();
            
            if ($originalExpiry === null) {
                $newExpiry = null;
            } else {
                $remainingDays = (int) now()->diffInDays($originalExpiry, false);

                if ($remainingDays <= 0) {
                    throw ValidationException::withMessages([
                        'booking_id' => 'The original package validity has fully elapsed. No resumption is possible.',
                    ]);
                }
                
                $newExpiry = now()->addDays($remainingDays);
            }

            $booking->update(['unfrozen_at' => now()]);

            return Booking::create([
                'user_id'           => $booking->user_id,
                'package_id'        => $booking->package_id,
                'total_credits'     => $booking->remaining_credits,
                'remaining_credits' => $booking->remaining_credits,
                'status'            => BookingStatusEnum::ACTIVE,
                'expires_at'        => $newExpiry,
                'source_type'       => BookingSourceTypeEnum::FREEZE_RESUME,
                'parent_booking_id' => $booking->id,
            ]);
        });
    }
}
