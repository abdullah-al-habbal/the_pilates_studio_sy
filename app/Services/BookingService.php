<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function createFromPackage(User $user, Package $package, ?Carbon $expiresAt = null): Booking
    {
        return DB::transaction(function () use ($user, $package, $expiresAt) {
            return Booking::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'total_credits' => $package->total_credits,
                'remaining_credits' => $package->total_credits,
                'status' => BookingStatusEnum::ACTIVE,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function adjustCredits(Booking $booking, int $amount, string $reason = 'Manual adjustment'): void
    {
        DB::transaction(function () use ($booking, $amount, $reason) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);

            $newRemaining = $booking->remaining_credits + $amount;

            if ($newRemaining < 0) {
                throw ValidationException::withMessages([
                    'remaining_credits' => 'Cannot reduce credits below zero.',
                ]);
            }

            if ($newRemaining > $booking->total_credits) {
                throw ValidationException::withMessages([
                    'remaining_credits' => 'Cannot exceed total credits.',
                ]);
            }

            $booking->update(['remaining_credits' => $newRemaining]);
        });
    }

    public function expireBooking(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => BookingStatusEnum::EXPIRED,
                'expires_at' => now(),
            ]);
        });
    }

    public function refundBooking(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => BookingStatusEnum::CANCELLED,
                'remaining_credits' => $booking->total_credits,
            ]);
        });
    }
}
