<?php

namespace App\Services;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingSessionService
{
    public function reserve(Booking $booking, ClassSession $classSession): BookingSession
    {
        return DB::transaction(function () use ($booking, $classSession) {

            $booking = Booking::lockForUpdate()->findOrFail($booking->id);

            if (!$booking->hasCreditsRemaining()) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Booking has no credits remaining.',
                ]);
            }

            $exists = BookingSession::query()
                ->where('booking_id', $booking->id)
                ->where('class_session_id', $classSession->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'class_session_id' => 'Session already reserved.',
                ]);
            }

            $classSession = ClassSession::lockForUpdate()->findOrFail($classSession->id);

            if ($classSession->available_spots <= 0) {
                throw ValidationException::withMessages([
                    'class_session_id' => 'Session is full.',
                ]);
            }

            $booking->decrement('remaining_credits');

            if ($booking->remaining_credits <= 0) {
                $booking->update(['status' => BookingStatusEnum::EXHAUSTED->value]);
            }

            return BookingSession::create([
                'booking_id' => $booking->id,
                'class_session_id' => $classSession->id,
                'status' => BookingSessionStatusEnum::RESERVED,
            ]);
        });
    }

    public function cancel(BookingSession $bookingSession): void
    {
        DB::transaction(function () use ($bookingSession) {

            $session = $bookingSession->classSession;

            $cutoff = Carbon::parse(
                $session->date->format('Y-m-d') . ' ' . $session->start_time
            )->subHours(24);

            if (now()->greaterThanOrEqualTo($cutoff)) {
                throw ValidationException::withMessages([
                    'cancelled_at' => 'Cancellation window has passed (24h before session).',
                ]);
            }

            $bookingSession->update([
                'status' => BookingSessionStatusEnum::CANCELLED,
                'cancelled_at' => now(),
            ]);

            $bookingSession->booking->refundCredit();
        });
    }

    public function markAttended(BookingSession $bookingSession): void
    {
        $bookingSession->update(['status' => BookingSessionStatusEnum::ATTENDED]);
    }

    public function markNoShow(BookingSession $bookingSession): void
    {
        $bookingSession->update(['status' => BookingSessionStatusEnum::NO_SHOW]);
    }
}


