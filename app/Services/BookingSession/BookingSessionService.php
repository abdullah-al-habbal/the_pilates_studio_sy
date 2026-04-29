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
use App\Services\Booking\BookingService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Log\LoggingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
class BookingSessionService
{
    public function __construct(
        private readonly BookingSessionEloquentRepository $repository,
        private readonly BookingEloquentRepository $bookingRepo,
        private readonly ClassSessionEloquentRepository $classSessionRepo,
        private readonly PackageEloquentRepository $packageRepo,
        private readonly UserEloquentRepository $userRepo,
        private readonly BookingService $bookingService,
        private readonly ClassSessionService $classSessionService,
        private readonly LoggingService $logger
    ) {
    }

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

    public function cancel(int $bookingSessionId): void
    {
        $this->logger->info('Cancelling booking session', ['session_id' => $bookingSessionId]);

        DB::transaction(function () use ($bookingSessionId) {
            $bookingSession = $this->findById($bookingSessionId, true);

            if ($bookingSession->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'Session is already cancelled.',
                ]);
            }

            $classSession = $this->classSessionService->find($bookingSession->class_session_id, true);

            $date = $classSession->date instanceof Carbon
                ? $classSession->date
                : Carbon::parse($classSession->date);

            $cutoff = Carbon::parse(
                $date->format('Y-m-d') . ' ' . $classSession->start_time
            )->subHours(24);

            if (now()->greaterThanOrEqualTo($cutoff)) {
                throw ValidationException::withMessages([
                    'cancelled_at' => 'Cancellation window has passed (24h before session).',
                ]);
            }

            $this->repository->updateStatus($bookingSessionId, BookingSessionStatusEnum::CANCELLED->value);
            $this->repository->setCancelledAt($bookingSessionId);
            $booking = $this->bookingService->find($bookingSession->booking_id);
            $this->bookingService->refundCredit($booking);
        });
    }

    public function markAttended(int $bookingSessionId): void
    {
        $this->logger->info('Marking session attended', ['session_id' => $bookingSessionId]);
        $this->repository->markAttended($bookingSessionId);
    }

    public function markMissed(int $bookingSessionId): void
    {
        $this->logger->info('Marking session missed', ['session_id' => $bookingSessionId]);
        $this->repository->markMissed($bookingSessionId);
    }

    public function toggleAttendance(int $bookingSessionId, AttendanceStatusEnum $status): void
    {
        $this->logger->info('Toggling attendance', [
            'session_id' => $bookingSessionId,
            'status' => $status->value,
        ]);

        if ($status === AttendanceStatusEnum::ATTENDED) {
            $this->repository->markAttended($bookingSessionId);
        } else {
            $this->repository->markMissed($bookingSessionId);
        }
    }

    public function oneTimeAttend(int $userId, int $classSessionId): void
    {
        DB::transaction(function () use ($userId, $classSessionId): void {

            $this->classSessionRepo->findOrFailForUpdate($classSessionId);

            $alreadyAttending = $this->repository->existsForUserAndClassSession($userId, $classSessionId);

            if ($alreadyAttending) {
                return;
            }

            $booking = $this->bookingRepo->findActiveWithCreditsForUser($userId);

            if (!$booking) {
                $package = $this->packageRepo->findActiveWalkInPackage();

                if (!$package) {
                    $package = $this->packageRepo->createWalkInPackage();
                }

                $booking = $this->bookingRepo->create([
                    'user_id' => $userId,
                    'package_id' => $package->id,
                    'total_credits' => 1,
                    'remaining_credits' => 1,
                    'status' => BookingStatusEnum::ACTIVE,
                ]);
            }

            $this->bookingService->decrementCredits($booking);

            $this->repository->create([
                'booking_id' => $booking->id,
                'class_session_id' => $classSessionId,
                'status' => 'reserved',
                'attendance_status' => AttendanceStatusEnum::ATTENDED,
            ]);
        });
    }

    public function createWalkInUser(array $data): User
    {
        return $this->userRepo->create([
            'fullname' => $data['fullname'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'password' => bcrypt($data['password'] ?? '12345678'),
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
            $booking = $this->bookingService->find($bookingId, true);

            $this->assertBookingHasCredits($booking);
            $this->assertBookingIsValid($booking);
            $this->assertNoDuplicateSessionForUser($booking->user_id, $classSessionId);
            $this->assertSessionHasAvailableSpots($classSessionId);

            $this->classSessionService->find($classSessionId, true);
            $this->bookingService->decrementCredits($booking);
            $booking->refresh();

            if (!$this->bookingService->hasCreditsRemaining($booking)) {
                $this->bookingService->updateStatus($booking, BookingStatusEnum::EXHAUSTED);
            }

            return $this->repository->create([
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
        if (!$this->classSessionService->hasAvailableSpots($classSessionId)) {
            throw ValidationException::withMessages([
                'class_session_id' => 'This session is fully booked.',
            ]);
        }
    }

    private function assertBookingHasCredits(Booking $booking): void
    {
        if (!$this->bookingService->hasCreditsRemaining($booking)) {
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
