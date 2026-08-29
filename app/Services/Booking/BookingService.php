<?php

// filePath: app/Services/Booking/BookingService.php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Commands\Booking\CreateBookingCommand;
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

    public function createBookingFromPackage(User $user, Package $package, ?Carbon $expiresAt = null, ?int $currencyId = null, ?int $paidAmount = null, ?float $exchangeRateSnapshot = null): Booking
    {
        return $this->createFromPackage(new CreateBookingCommand(
            user: $user,
            package: $package,
            expiresAt: $expiresAt,
            currencyId: $currencyId,
            paidAmount: $paidAmount,
            exchangeRateSnapshot: $exchangeRateSnapshot,
        ));
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

    public function assertNoActiveBooking(User $user, bool $lock = false): void
    {
        // findBlockingActiveBooking() matches the generated column behind
        // unique_active_booking_per_user exactly: status=active AND remaining_credits>0, with no
        // expiry clause. The expiry-filtered predicate this used to call was blind to a booking
        // past expires_at that still says active with credits left — routine, since nothing
        // expires bookings on a schedule — so the guard passed and the insert died on a raw 1062.
        $conflict = $this->repository->findBlockingActiveBooking($user->id, $lock);

        if ($conflict === null) {
            return;
        }

        throw ValidationException::withMessages([
            'user_id' => __('dashboard.operations_ui.errors.active_booking_exists', [
                'package_name' => $conflict->package?->name ?? '—',
                'remaining_credits' => $conflict->remaining_credits,
            ]),
        ]);
    }

    public function createFromPackage(CreateBookingCommand $command): Booking
    {
        return DB::transaction(function () use ($command): Booking {
            // Reconcile first: a row past its expiry that still says active holds the index for
            // no reason, and would otherwise block a purchase the client is entitled to make.
            $this->repository->expireStaleActiveBookings($command->user->id);

            // Guard runs INSIDE the transaction and takes a row lock. Outside it, two concurrent
            // requests for the same user both passed the check and both inserted — a transaction
            // alone would not have stopped that, since it gives atomicity, not mutual exclusion.
            $this->assertNoActiveBooking($command->user, lock: true);

            return $this->repository->create([
                'user_id' => $command->user->id,
                'created_by' => $command->createdBy,
                'package_id' => $command->package->id,
                'total_credits' => $command->package->total_credits,
                'remaining_credits' => $command->package->total_credits,
                'status' => BookingStatusEnum::ACTIVE->value,
                'expires_at' => $command->expiresAt,
                'purchased_at' => now(),
                'currency_id' => $command->currencyId,
                'paid_amount' => $command->paidAmount,
                'exchange_rate_snapshot' => $command->exchangeRateSnapshot,
            ]);
        });
    }

    public function adjustCredits(Booking $booking, int $amount): void
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

            $this->repository->updateRemainingCredits($locked->id, $newRemaining);
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
        $this->repository->expire($booking->id);
    }

    public function cancelBooking(Booking $booking): void
    {
        $this->repository->cancel($booking->id);
    }

    public function updateStatus(Booking $booking, BookingStatusEnum $status): void
    {
        $this->repository->updateStatus($booking->id, $status);
    }

    public function hasCreditsRemaining(Booking $booking): bool
    {
        return $booking->remaining_credits > 0;
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

    public function getRevenueByPackage(): Collection
    {
        return $this->repository->getRevenueByPackage();
    }
}
