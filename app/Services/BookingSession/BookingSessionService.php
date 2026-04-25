<?php

// filePath: app/Services/BookingSession/BookingSessionService.php

declare(strict_types=1);

namespace App\Services\BookingSession;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use App\Services\Booking\BookingService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Log\LoggingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingSessionService
{
    public function __construct(
        private readonly BookingSessionEloquentRepository $repository,
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
        $bookingSession = $this->findById($bookingSessionId);
        $bookingSession->markAttended();
    }

    public function markMissed(int $bookingSessionId): void
    {
        $this->logger->info('Marking session missed', ['session_id' => $bookingSessionId]);
        $bookingSession = $this->findById($bookingSessionId);
        $bookingSession->markMissed();
    }

    public function toggleAttendance(int $bookingSessionId, AttendanceStatusEnum $status): void
    {
        $this->logger->info('Toggling attendance', [
            'session_id' => $bookingSessionId,
            'status' => $status->value,
        ]);

        $bookingSession = $this->findById($bookingSessionId);
        if ($status === AttendanceStatusEnum::ATTENDED) {
            $bookingSession->markAttended();
        } else {
            $bookingSession->markMissed();
        }
    }

    public function oneTimeAttend(int $userId, int $classSessionId): void
    {
        DB::transaction(function () use ($userId, $classSessionId): void {

            $session = ClassSession::lockForUpdate()->findOrFail($classSessionId);

            $alreadyAttending = BookingSession::whereHas('booking', fn($q) => $q->where('user_id', $userId))
                ->where('class_session_id', $classSessionId)
                ->exists();

            if ($alreadyAttending) {
                return;
            }

            // Resolve active booking with credits
            $booking = Booking::where('user_id', $userId)
                ->where('status', BookingStatusEnum::ACTIVE)
                ->where('remaining_credits', '>', 0)
                ->lockForUpdate()
                ->first();

            if (!$booking) {
                // Find or create a 1-credit walk-in package
                $package = Package::where('total_credits', 1)
                    ->where('is_active', true)
                    ->first();

                if (!$package) {
                    $package = Package::create([
                        'name' => ['en' => 'Walk-in Session', 'ar' => 'جلسة مباشرة'],
                        'total_credits' => 1,
                        'price' => 0,
                        'is_active' => true,
                    ]);
                }

                $booking = Booking::create([
                    'user_id' => $userId,
                    'package_id' => $package->id,
                    'total_credits' => 1,
                    'remaining_credits' => 1,
                    'status' => BookingStatusEnum::ACTIVE,
                ]);
            }

            $booking->deductCredit();

            BookingSession::create([
                'booking_id' => $booking->id,
                'class_session_id' => $classSessionId,
                'status' => 'reserved',
                'attendance_status' => AttendanceStatusEnum::ATTENDED,
            ]);
        });
    }

    public function createWalkInUser(array $data): User
    {
        return User::create([
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
        return BookingSession::where('attendance_status', AttendanceStatusEnum::ATTENDED)->count();
    }

    public function countMissed(): int
    {
        return BookingSession::where('attendance_status', AttendanceStatusEnum::MISSED)->count();
    }

    public function countMissedForMonth(int $month, ?int $year = null): int
    {
        $year = $year ?? now()->year;

        return BookingSession::where('attendance_status', AttendanceStatusEnum::MISSED)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();
    }

    public function countCancelled(): int
    {
        return BookingSession::where('status', BookingSessionStatusEnum::CANCELLED)->count();
    }

    public function getAttendanceTrend(int $days = 30): \Illuminate\Support\Collection
    {
        $startDate = now()->subDays($days)->startOfDay();
        $sessions = BookingSession::where('attendance_status', AttendanceStatusEnum::ATTENDED)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $dates = collect();
        for ($i = 0; $i <= $days; $i++) {
            $date = now()->subDays($days - $i)->toDateString();
            $dates->put($date, $sessions->get($date, 0));
        }

        return $dates;
    }

    public function totalSessionsCount(): int
    {
        return BookingSession::count();
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
}
