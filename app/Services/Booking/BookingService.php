<?php

// filePath: app/Services/Booking/BookingService.php
declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly BookingEloquentRepository $repository,
    ) {}

    public function userHasActiveCreditBooking(User $user): bool
    {
        return $this->repository->userHasActiveCreditBooking($user->id);
    }

    public function createBookingFromPackage(User $user, Package $package, ?Carbon $expiresAt = null): Booking
    {
        return $this->createFromPackage($user, $package, $expiresAt);
    }

    public function find(int $id, bool $lockForUpdate = false, array $relations = []): Booking
    {
        $booking = $this->repository->find($id, $lockForUpdate, $relations);

        return $booking ?? throw new ModelNotFoundException;
    }

    public function findByUser(int $userId, int $id, array $relations = []): Booking
    {
        $booking = $this->repository->findByUser($userId, $id, $relations);

        return $booking ?? throw new ModelNotFoundException;
    }

    public function listUserBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->listUserBookings($userId, $filters);
    }

    public function assertNoActiveBooking(User $user): void
    {
        $hasActive = Booking::where('user_id', $user->id)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->exists();

        if ($hasActive) {
            throw ValidationException::withMessages([
                'user_id' => 'User already has an active booking with remaining credits.',
            ]);
        }
    }

    public function createFromPackage(User $user, Package $package, ?Carbon $expiresAt = null): Booking
    {
        $this->assertNoActiveBooking($user);

        return DB::transaction(function () use ($user, $package, $expiresAt): Booking {
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
        DB::transaction(function () use ($booking, $amount): void {
            $locked = $this->find($booking->id, lockForUpdate: true);

            $newRemaining = $locked->remaining_credits + $amount;

            if ($newRemaining < 0) {
                throw ValidationException::withMessages([
                    'remaining_credits' => 'Cannot reduce credits below zero.',
                ]);
            }

            if ($newRemaining > $locked->total_credits) {
                throw ValidationException::withMessages([
                    'remaining_credits' => 'Cannot exceed total credits.',
                ]);
            }

            $locked->update(['remaining_credits' => $newRemaining]);
        });
    }

    public function decrementCredits(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $this->repository->decrementCredits($booking->id);
        });
    }

    public function refundCredit(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $this->repository->refundCredit($booking->id);
        });
    }

    public function expireBooking(Booking $booking): void
    {
        $booking->update([
            'status' => BookingStatusEnum::EXPIRED,
            'expires_at' => now(),
        ]);
    }

    public function cancelBooking(Booking $booking): void
    {
        $booking->update([
            'status' => BookingStatusEnum::CANCELLED,
            'remaining_credits' => $booking->total_credits,
        ]);
    }

    public function updateStatus(Booking $booking, BookingStatusEnum $status): void
    {
        $booking->update(['status' => $status]);
    }

    public function hasCreditsRemaining(Booking $booking): bool
    {
        return $booking->hasCreditsRemaining();
    }

    public function countActive(): int
    {
        return $this->repository->countActive();
    }

    public function sumTotalCredits(): int
    {
        return $this->repository->sumTotalCredits();
    }

    public function sumUsedCredits(): int
    {
        return $this->repository->sumUsedCredits();
    }

    public function createWalkInBooking(int $userId): Booking
    {
        return DB::transaction(function () use ($userId): Booking {
            $package = Package::firstOrCreate(
                ['name->en' => 'Walk-in Pass'],
                [
                    'name->ar' => 'خدمة لمرة واحدة',
                    'total_credits' => 1,
                    'price' => 0,
                    'status' => 'active',
                ]
            );

            return Booking::create([
                'user_id' => $userId,
                'package_id' => $package->id,
                'total_credits' => 1,
                'remaining_credits' => 1,
                'status' => BookingStatusEnum::ACTIVE,
                'expires_at' => now()->endOfDay(),
            ]);
        });
    }

    public function getRevenueByPackage(): Collection
    {
        return $this->repository->getRevenueByPackage();
    }
}
