<?php
// filePath: app/Services/Booking/BookingService.php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Services\Log\LoggingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly BookingEloquentRepository $bookingRepository,
        private readonly LoggingService $logger
    ) {}

    public function createFromPackage(User $user, Package $package, ?Carbon $expiresAt = null): Booking
    {
        return DB::transaction(function () use ($user, $package, $expiresAt) {
            $this->logger->info('Creating booking from package', [
                'user_id' => $user->id,
                'package_id' => $package->id
            ]);

            $booking = Booking::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'total_credits' => $package->total_credits,
                'remaining_credits' => $package->total_credits,
                'status' => BookingStatusEnum::ACTIVE,
                'expires_at' => $expiresAt,
            ]);

            $this->logger->info('Booking created successfully', ['booking_id' => $booking->id]);
            return $booking;
        });
    }

    public function listUserBookings(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            return $this->bookingRepository->listUserBookings($user->id, $filters);
        } catch (\Exception $e) {
            $this->logger->error('List user bookings failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function findUserBooking(User $user, int $id): Booking
    {
        $booking = $this->bookingRepository->findByUser($user->id, $id);
        return $booking ?? throw new ModelNotFoundException;
    }

    public function adjustCredits(Booking $booking, int $amount, string $reason = 'Manual adjustment'): void
    {
        DB::transaction(function () use ($booking, $amount, $reason) {
            $this->logger->info('Adjusting booking credits', [
                'booking_id' => $booking->id,
                'amount' => $amount,
                'reason' => $reason
            ]);

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
            $this->logger->info('Expiring booking', ['booking_id' => $booking->id]);
            $booking->update([
                'status' => BookingStatusEnum::EXPIRED,
                'expires_at' => now(),
            ]);
        });
    }

    public function refundBooking(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $this->logger->info('Refunding booking', ['booking_id' => $booking->id]);
            $booking->update([
                'status' => BookingStatusEnum::CANCELLED,
                'remaining_credits' => $booking->total_credits,
            ]);
        });
    }

    public function lockForUpdate(int $bookingId): Booking
    {
        return $this->bookingRepository->lockForUpdate($bookingId);
    }

    public function hasCreditsRemaining(int $bookingId): bool
    {
        $booking = $this->bookingRepository->find($bookingId);
        return $booking?->hasCreditsRemaining() ?? false;
    }

    public function decrementCredits(int $bookingId): void
    {
        DB::transaction(function () use ($bookingId) {
            $this->bookingRepository->decrementCredits($bookingId);
        });
    }

    public function updateStatus(int $bookingId, string $status): bool
    {
        return $this->bookingRepository->updateStatus($bookingId, $status);
    }

    public function refundCredit(int $bookingId): void
    {
        $this->bookingRepository->refundCredit($bookingId);
    }
}
