<?php

namespace App\Observers;

use App\Enums\BookingSessionStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use Illuminate\Validation\ValidationException;

class BookingSessionObserver
{
    public function creating(BookingSession $bookingSession): void
    {
        $this->assertNoDuplicateSessionForUser($bookingSession);
        $this->assertSessionHasAvailableSpots($bookingSession);
        $this->assertBookingHasCredits($bookingSession);
    }

    public function updating(BookingSession $bookingSession): void
    {
        if (
            $bookingSession->isDirty('status') &&
            $bookingSession->status === BookingSessionStatusEnum::RESERVED &&
            $bookingSession->getOriginal('status') === BookingSessionStatusEnum::CANCELLED->value
        ) {
            $this->assertBookingHasCredits($bookingSession);
            $this->assertSessionHasAvailableSpots($bookingSession);
        }
    }

    private function assertNoDuplicateSessionForUser(BookingSession $bookingSession): void
    {
        $userId = Booking::query()
            ->where('id', $bookingSession->booking_id)
            ->value('user_id');

        $alreadyBooked = BookingSession::query()
            ->whereIn('status', [
                BookingSessionStatusEnum::RESERVED->value,
                BookingSessionStatusEnum::ATTENDED->value,
            ])
            ->where('class_session_id', $bookingSession->class_session_id)
            ->whereHas('booking', fn ($q) => $q->where('user_id', $userId))
            ->exists();

        if ($alreadyBooked) {
            throw ValidationException::withMessages([
                'class_session_id' => 'This user has already reserved this session.',
            ]);
        }
    }

    private function assertSessionHasAvailableSpots(BookingSession $bookingSession): void
    {
        $session = $bookingSession->classSession ??
            ClassSession::findOrFail($bookingSession->class_session_id);

        if ($session->isFull()) {
            throw ValidationException::withMessages([
                'class_session_id' => 'This session is fully booked.',
            ]);
        }
    }

    private function assertBookingHasCredits(BookingSession $bookingSession): void
    {
        $booking = $bookingSession->booking ??
            Booking::findOrFail($bookingSession->booking_id);

        if (! $booking->hasCreditsRemaining()) {
            throw ValidationException::withMessages([
                'booking_id' => 'This booking has no remaining credits.',
            ]);
        }
    }
}
