<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Data\Booking\HistoricalBackfillPlan;
use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingSourceTypeEnum;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\Log\LoggingService;
use App\Support\Booking\ActiveBookingConstraint;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class HistoricalBackfillService
{
    private const LANG = 'dashboard.operations_ui.historical_backfill.';

    public function __construct(
        private BookingEloquentRepository $bookingRepository,
        private BookingSessionEloquentRepository $bookingSessionRepository,
        private ClassSessionEloquentRepository $classSessionRepository,
        private LoggingService $logger,
    ) {}

    public function backfill(HistoricalBackfillPlan $plan, ?int $createdBy = null): Booking
    {
        $this->logAttempt($plan, $createdBy);

        try {
            $booking = DB::transaction(function () use ($plan, $createdBy): Booking {
                $sessions = $this->lockSessions($plan->allSessionIds());

                $this->assertNoDuplicateSessionsForUser($plan);

                $booking = $this->createBooking($plan, $createdBy);

                $this->createSessions($plan, $booking, $sessions, $createdBy);

                return $booking;
            });
        } catch (QueryException $e) {
            throw $this->translateDuplicateActiveBooking($e, $plan, $createdBy);
        } catch (ValidationException $e) {
            $this->logFailure('Historical backfill rejected during write.', $plan, $createdBy, $e->getMessage());

            throw $e;
        }

        $this->logSuccess($plan, $booking, $createdBy);

        return $booking;
    }

    private function lockSessions(array $sessionIds): array
    {
        $sessions = [];

        foreach ($sessionIds as $id) {
            $sessions[$id] = $this->classSessionRepository->findOrFailForUpdate($id);
        }

        return $sessions;
    }

    private function assertNoDuplicateSessionsForUser(HistoricalBackfillPlan $plan): void
    {
        foreach ($plan->allSessionIds() as $sessionId) {
            $exists = $this->bookingSessionRepository
                ->existsForUserAndClassSession($plan->user->id, $sessionId);

            if ($exists) {
                throw ValidationException::withMessages([
                    'attended_session_ids' => __(self::LANG . 'error_duplicate_session', [
                        'session_id' => $sessionId,
                    ]),
                ]);
            }
        }
    }

    private function createBooking(HistoricalBackfillPlan $plan, ?int $createdBy): Booking
    {
        return $this->bookingRepository->create([
            'user_id' => $plan->user->id,
            'created_by' => $createdBy,
            'package_id' => $plan->package->id,
            'total_credits' => $plan->totalCredits(),
            'remaining_credits' => $plan->remainingCredits,
            'status' => $plan->terminalStatus->value,
            'source_type' => BookingSourceTypeEnum::HISTORICAL_BACKFILL->value,
            'purchased_at' => $plan->purchasedAt,
            'expires_at' => $plan->expiresAt,
            'currency_id' => $plan->currencyId,
            'paid_amount' => $plan->paidAmount,
            'exchange_rate_snapshot' => $plan->exchangeRateSnapshot,
        ]);
    }

    private function createSessions(
        HistoricalBackfillPlan $plan,
        Booking $booking,
        array $sessions,
        ?int $createdBy,
    ): void {
        foreach ($plan->attendedSessionIds as $id) {
            $this->bookingSessionRepository->create([
                'booking_id' => $booking->id,
                'class_session_id' => $id,
                'status' => BookingSessionStatusEnum::RESERVED->value,
                'attendance_status' => AttendanceStatusEnum::ATTENDED->value,
                'attended_at' => $this->sessionStartsAt($sessions[$id]),
                'attendance_updated_by' => $createdBy,
            ]);
        }

        foreach ($plan->missedSessionIds as $id) {
            $this->bookingSessionRepository->create([
                'booking_id' => $booking->id,
                'class_session_id' => $id,
                'status' => BookingSessionStatusEnum::RESERVED->value,
                'attendance_status' => AttendanceStatusEnum::MISSED->value,
                'attended_at' => null,
                'attendance_updated_by' => $createdBy,
            ]);
        }
    }

    private function sessionStartsAt(ClassSession $session): Carbon
    {
        $date = $session->date instanceof Carbon
            ? $session->date
            : Carbon::parse((string) $session->date);

        return Carbon::parse($date->format('Y-m-d') . ' ' . $session->start_time);
    }

    private function translateDuplicateActiveBooking(
        QueryException $e,
        HistoricalBackfillPlan $plan,
        ?int $createdBy,
    ): \Throwable {
        if (! ActiveBookingConstraint::isViolatedBy($e)) {
            $this->logFailure('Historical backfill failed with a database error.', $plan, $createdBy, $e->getMessage());

            return $e;
        }

        $this->logFailure('Historical backfill hit the active-booking constraint.', $plan, $createdBy, $e->getMessage());

        return ValidationException::withMessages([
            'package_id' => __(self::LANG . 'error_active_booking_conflict', [
                'client_name' => $plan->user->fullname,
                'package_name' => $plan->package->name,
                'remaining_credits' => 0,
            ]),
        ]);
    }

    private function logAttempt(HistoricalBackfillPlan $plan, ?int $createdBy): void
    {
        $this->logger->info('Historical backfill attempted.', $this->context($plan, $createdBy));
    }

    private function logSuccess(HistoricalBackfillPlan $plan, Booking $booking, ?int $createdBy): void
    {
        $this->logger->info('Historical backfill committed.', [
            ...$this->context($plan, $createdBy),
            'booking_id' => $booking->id,
        ]);

        if ($plan->exchangeRateWasOverridden) {
            $this->logger->info('Historical backfill used admin-provided exchange rate.', [
                'admin_id' => $createdBy,
                'user_id' => $plan->user->id,
                'provided_rate' => $plan->exchangeRateSnapshot,
                'current_rate' => $plan->currentExchangeRate,
            ]);

            return;
        }

        $this->logger->warning('Historical backfill used the current exchange rate for an old transaction.', [
            'admin_id' => $createdBy,
            'user_id' => $plan->user->id,
            'rate' => $plan->exchangeRateSnapshot,
            'purchased_at' => $plan->purchasedAt->toDateString(),
        ]);
    }

    private function logFailure(string $message, HistoricalBackfillPlan $plan, ?int $createdBy, string $reason): void
    {
        $this->logger->error($message, [
            ...$this->context($plan, $createdBy),
            'reason' => $reason,
        ]);
    }
    private function context(HistoricalBackfillPlan $plan, ?int $createdBy): array
    {
        return [
            'admin_id' => $createdBy,
            'user_id' => $plan->user->id,
            'package_id' => $plan->package->id,
            'purchased_at' => $plan->purchasedAt->toDateTimeString(),
            'attended_count' => $plan->attendedCount(),
            'missed_count' => $plan->missedCount(),
            'remaining_credits' => $plan->remainingCredits,
            'terminal_status' => $plan->terminalStatus->value,
            'attended_session_ids' => $plan->attendedSessionIds,
            'missed_session_ids' => $plan->missedSessionIds,
            'ip' => request()->ip(),
        ];
    }
}
