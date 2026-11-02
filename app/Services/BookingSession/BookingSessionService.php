<?php

// filePath: app/Services/BookingSession/BookingSessionService.php

declare(strict_types=1);

namespace App\Services\BookingSession;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\User;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Repositories\Eloquent\Package\PackageEloquentRepository;
use App\Repositories\Eloquent\User\UserEloquentRepository;
use App\Services\AppSetting\AppSettingService;
use App\Services\Booking\BookingService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Currency\PricingService;
use App\Services\Log\LoggingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BookingSessionService
{
    private const CANCELLATION_USER_INITIATED = 0;

    private const CANCELLATION_ADMIN_OVERRIDE = 1;

    public function __construct(
        private readonly BookingSessionEloquentRepository $repository,
        private readonly BookingEloquentRepository $bookingRepo,
        private readonly ClassSessionEloquentRepository $classSessionRepo,
        private readonly PackageEloquentRepository $packageRepo,
        private readonly UserEloquentRepository $userRepo,
        private readonly BookingService $bookingService,
        private readonly ClassSessionService $classSessionService,
        private readonly LoggingService $logger,
        private readonly PricingService $pricingService,
        private readonly AppSettingService $appSettings,
        private readonly CancellationPolicyService $cancellationPolicy,
    ) {}

    public function listUserSessions(int $userId, array $filters = []): LengthAwarePaginator
    {
        $this->logger->info('Listing user sessions', ['user_id' => $userId]);

        return $this->repository->listUserSessions($userId, $filters);
    }

    public function findUserSession(int $userId, int $id): BookingSession
    {
        return $this->repository->findByUser($userId, $id)
            ?? throw new ModelNotFoundException;
    }

    /**
     * Cancels a reservation and gives the credit back.
     *
     * Locks are taken in the same order as reserve(): class session, reservation, booking.
     * A caller who may not cancel this reservation gets "not found", so ids cannot be probed.
     * Only an admin actor can use the override, and even then not once the class has started.
     */
    public function cancel(
        int $bookingSessionId,
        User $actor,
        bool $adminOverride = false,
        ?string $reason = null,
    ): BookingSession {
        $isOverride = $adminOverride && $actor->isAdmin();

        $this->logger->info('Cancelling booking session', [
            'session_id' => $bookingSessionId,
            'actor_id' => $actor->id,
            'admin_override' => $isOverride,
        ]);

        return DB::transaction(function () use ($bookingSessionId, $actor, $isOverride, $reason): BookingSession {
            $probe = $this->repository->find($bookingSessionId) ?? throw $this->notFound($bookingSessionId);

            $classSession = $this->classSessionService->find($probe->class_session_id, true);

            $bookingSession = $this->repository->find($bookingSessionId, true) ?? throw $this->notFound($bookingSessionId);

            if (! Gate::forUser($actor)->allows('cancel', $bookingSession)) {
                $this->logger->warning('Cancel denied for booking session', [
                    'session_id' => $bookingSessionId,
                    'actor_id' => $actor->id,
                ]);

                throw $this->notFound($bookingSessionId);
            }

            if ($bookingSession->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'Session is already cancelled.',
                ]);
            }

            if ($bookingSession->attended_at !== null) {
                throw ValidationException::withMessages([
                    'status' => 'Attendance has already been recorded for this session.',
                ]);
            }

            $this->cancellationPolicy->assertCancellable($classSession, $isOverride);

            $booking = $this->bookingService->find($bookingSession->booking_id, true);

            $this->repository->markCancelled(
                id: $bookingSessionId,
                actorId: $actor->id,
                adminId: $actor->isAdmin() ? $actor->id : null,
                cancellationType: $isOverride ? self::CANCELLATION_ADMIN_OVERRIDE : self::CANCELLATION_USER_INITIATED,
                reason: $reason,
            );

            $this->bookingService->restoreCreditAfterCancellation($booking);

            return $bookingSession->refresh();
        });
    }

    private function notFound(int $bookingSessionId): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel(BookingSession::class, [$bookingSessionId]);
    }

    public function markAttended(int $bookingSessionId, ?int $updatedByAdminId = null): void
    {
        $this->logger->info('Marking session attended', ['session_id' => $bookingSessionId]);
        $this->repository->markAttended($bookingSessionId, $updatedByAdminId);
    }

    public function markMissed(int $bookingSessionId, ?int $updatedByAdminId = null): void
    {
        $this->logger->info('Marking session missed', ['session_id' => $bookingSessionId]);
        $this->repository->markMissed($bookingSessionId, $updatedByAdminId);
    }

    public function toggleAttendance(int $bookingSessionId, AttendanceStatusEnum $status, ?int $updatedByAdminId = null): void
    {
        $this->logger->info('Toggling attendance', [
            'session_id' => $bookingSessionId,
            'status' => $status->value,
        ]);

        if ($status === AttendanceStatusEnum::ATTENDED) {
            $this->repository->markAttended($bookingSessionId, $updatedByAdminId);
        } else {
            $this->repository->markMissed($bookingSessionId, $updatedByAdminId);
        }
    }

    public function oneTimeAttend(int $userId, int $classSessionId, ?int $createdBy = null): void
    {
        DB::transaction(function () use ($userId, $classSessionId, $createdBy): void {

            $this->classSessionRepo->findOrFailForUpdate($classSessionId);

            $alreadyAttending = $this->repository->existsForUserAndClassSession($userId, $classSessionId);

            if ($alreadyAttending) {
                return;
            }

            // Reconcile before looking: a row past its expiry that still says active is useless
            // to spend from, yet still holds unique_active_booking_per_user — so without this the
            // lookup below returns null, the walk-in branch inserts a second active row, and the
            // insert dies on the index with nothing catching it.
            $this->bookingRepo->expireStaleActiveBookings($userId);

            $booking = $this->bookingRepo->findActiveWithCreditsForUser($userId);

            if (! $booking) {
                // Deliberately NOT widening the lookup above to the index predicate: spending a
                // credit from an expired booking would be wrong. If something still occupies the
                // index after reconciliation it is a valid booking, and creating a walk-in
                // alongside it is what the constraint exists to prevent.
                $blocking = $this->bookingRepo->findBlockingActiveBooking($userId, lock: true);

                if ($blocking !== null) {
                    throw ValidationException::withMessages([
                        'user_id' => __('dashboard.operations_ui.errors.active_booking_exists', [
                            'package_name' => $blocking->package?->name ?? '—',
                            'remaining_credits' => $blocking->remaining_credits,
                        ]),
                    ]);
                }

                $price = (int) $this->appSettings->get('walk_in_price', 16);
                $package = $this->packageRepo->findOrCreateWalkInPackage($price);

                $currencyId = $this->pricingService->getBaseCurrencyId();
                $paidAmount = $package->getPriceForCurrency($currencyId);

                if ($paidAmount === null) {
                    throw new \RuntimeException('Walk-in package has no base price configured.');
                }

                $booking = $this->bookingRepo->create([
                    'user_id' => $userId,
                    'package_id' => $package->id,
                    'total_credits' => 1,
                    'remaining_credits' => 1,
                    'status' => BookingStatusEnum::ACTIVE,
                    'created_by' => $createdBy,
                    'paid_amount' => $paidAmount,
                    'currency_id' => $currencyId,
                    'exchange_rate_snapshot' => $this->pricingService->getExchangeRateForSnapshot($currencyId),
                ]);
            }

            $this->bookingService->decrementCredits($booking);

            $this->repository->create([
                'booking_id' => $booking->id,
                'class_session_id' => $classSessionId,
                'status' => 'reserved',
                'attendance_status' => AttendanceStatusEnum::ATTENDED,
                'attendance_updated_by' => $createdBy,
            ]);
        });
    }

    public function createWalkInUser(array $data): User
    {
        return $this->userRepo->create([
            'fullname' => $data['fullname'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'password' => bcrypt($data['password'] ?? \Str::random(16)),
        ]);
    }

    private function findById(int $id, bool $lockForUpdate = false): BookingSession
    {
        return $this->repository->find($id, $lockForUpdate);
    }

    public function reserve(int $bookingId, int $classSessionId): BookingSession
    {
        $this->logger->info('Reserving session', [
            'booking_id' => $bookingId,
            'class_session_id' => $classSessionId,
        ]);

        return DB::transaction(function () use ($bookingId, $classSessionId) {
            // Lock the class session BEFORE counting its reservations. The
            // capacity check used to run against an unlocked read, so two
            // concurrent requests for the last spot both passed it and then
            // serialised on the lock, and both inserted.
            //
            // Lock order is class session then booking, matching
            // oneTimeAttend(); the two used to acquire them in opposite orders,
            // which is a deadlock waiting to happen.
            $this->classSessionService->find($classSessionId, true);

            $booking = $this->bookingService->find($bookingId, true);

            $this->assertBookingHasCredits($booking);
            $this->assertBookingIsValid($booking);
            $this->assertNoDuplicateSessionForUser($booking->user_id, $classSessionId);
            $this->assertSessionHasAvailableSpots($classSessionId);

            $this->bookingService->decrementCredits($booking);
            $booking->refresh();

            if (! $this->bookingService->hasCreditsRemaining($booking)) {
                $this->bookingService->updateStatus($booking, BookingStatusEnum::EXHAUSTED);
            }

            return $this->repository->reviveCancelled($bookingId, $classSessionId)
                ?? $this->repository->create([
                    'booking_id' => $bookingId,
                    'class_session_id' => $classSessionId,
                    'status' => BookingSessionStatusEnum::RESERVED,
                ]);
        });
    }

    public function countAttended(): int
    {
        return $this->repository->countAttended();
    }

    public function countMissed(): int
    {
        return $this->repository->countMissed();
    }

    public function countMissedForMonth(int $month, ?int $year = null): int
    {
        $year = $year ?? now()->year;

        return $this->repository->countMissedForMonth($month, $year);
    }

    public function countCancelled(): int
    {
        return $this->repository->countCancelled();
    }

    public function getAttendanceTrend(int $days = 30): Collection
    {
        $sessions = $this->repository->getAttendanceTrend($days);

        $dates = collect();
        for ($i = 0; $i <= $days; $i++) {
            $date = now()->subDays($days - $i)->toDateString();
            $dates->put($date, $sessions->get($date, 0));
        }

        return $dates;
    }

    public function totalSessionsCount(): int
    {
        return $this->repository->totalSessionsCount();
    }

    private function assertNoDuplicateSessionForUser(int $userId, int $classSessionId): void
    {
        if ($this->repository->existsForUserAndClassSession($userId, $classSessionId)) {
            throw ValidationException::withMessages([
                'class_session_id' => 'This user has already reserved this session.',
            ]);
        }
    }

    private function assertSessionHasAvailableSpots(int $classSessionId): void
    {
        if (! $this->classSessionService->hasAvailableSpots($classSessionId)) {
            throw ValidationException::withMessages([
                'class_session_id' => 'This session is fully booked.',
            ]);
        }
    }

    private function assertBookingHasCredits(Booking $booking): void
    {
        if (! $this->bookingService->hasCreditsRemaining($booking)) {
            throw ValidationException::withMessages([
                'booking_id' => 'This booking has no remaining credits.',
            ]);
        }
    }

    private function assertBookingIsValid(Booking $booking): void
    {
        if ($booking->isExpired()) {
            throw ValidationException::withMessages([
                'booking_id' => 'This package has expired. New reservations are not permitted.',
            ]);
        }

        if ($booking->status !== BookingStatusEnum::ACTIVE) {
            throw ValidationException::withMessages([
                'booking_id' => 'This package is not in an active state.',
            ]);
        }
    }
}
