<?php

// filePath: app/Services/BookingSession/BookingSessionService.php

declare(strict_types=1);

namespace App\Services\BookingSession;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\BookingSession;
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
                $date->format('Y-m-d').' '.$classSession->start_time
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
        $this->repository->updateStatus($bookingSessionId, BookingSessionStatusEnum::ATTENDED->value);
    }

    public function markNoShow(int $bookingSessionId): void
    {
        $this->logger->info('Marking session no-show', ['session_id' => $bookingSessionId]);
        $this->repository->updateStatus($bookingSessionId, BookingSessionStatusEnum::NO_SHOW->value);
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

            if (! $this->bookingService->hasCreditsRemaining($booking)) {
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
        return BookingSession::where('status', BookingSessionStatusEnum::ATTENDED)->count();
    }

    public function countNoShows(): int
    {
        return BookingSession::where('status', BookingSessionStatusEnum::NO_SHOW)->count();
    }

    public function countNoShowsForMonth(int $month, ?int $year = null): int
    {
        $year = $year ?? now()->year;

        return BookingSession::where('status', BookingSessionStatusEnum::NO_SHOW)
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
        $sessions = BookingSession::where('status', BookingSessionStatusEnum::ATTENDED)
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
        if (! $this->classSessionService->hasAvailableSpots($classSessionId)) {
            throw ValidationException::withMessages([
                'class_session_id' => 'This session is fully booked.',
            ]);
        }
    }

    private function assertBookingHasCredits(\App\Models\Booking $booking): void
    {
        if (! $this->bookingService->hasCreditsRemaining($booking)) {
            throw ValidationException::withMessages([
                'booking_id' => 'This booking has no remaining credits.',
            ]);
        }
    }
}
