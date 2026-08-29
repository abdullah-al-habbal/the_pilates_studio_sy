<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingSourceTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingFreezeService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingEloquentRepository $bookingRepository,
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
                'status' => BookingStatusEnum::FROZEN,
                'frozen_at' => now(),
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

            $originalExpiry = $booking->expires_at;
            $frozenAt = $booking->frozen_at ?? now();

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

            $remainingCredits = $booking->remaining_credits;

            // The replacement below is always active with credits, so it always contends for
            // unique_active_booking_per_user. Without this check the insert raised a raw 1062 and
            // UnfreezeBookingAction handed the SQL string straight to the admin.
            $this->bookingRepository->expireStaleActiveBookings($booking->user_id);

            $blocking = $this->bookingRepository->findBlockingActiveBooking($booking->user_id, lock: true);

            if ($blocking !== null) {
                throw ValidationException::withMessages([
                    'booking_id' => __('dashboard.operations_ui.errors.active_booking_exists', [
                        'package_name' => $blocking->package?->name ?? '—',
                        'remaining_credits' => $blocking->remaining_credits,
                    ]),
                ]);
            }

            $booking->update([
                'unfrozen_at' => now(),
                'status' => BookingStatusEnum::CANCELLED,
                'remaining_credits' => 0,
            ]);

            return Booking::create([
                'user_id' => $booking->user_id,
                'package_id' => $booking->package_id,
                'total_credits' => $remainingCredits,
                'remaining_credits' => $remainingCredits,
                'status' => BookingStatusEnum::ACTIVE,
                'expires_at' => $newExpiry,
                'source_type' => BookingSourceTypeEnum::FREEZE_RESUME,
                'parent_booking_id' => $booking->id,
            ]);
        });
    }
}
