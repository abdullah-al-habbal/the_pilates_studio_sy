<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingSessionPolicy
{
    use HandlesAuthorization;

    public function reserve(User $user, Booking $booking, ClassSession $classSession): bool
    {
        return $booking->user_id === $user->id
            && $booking->isActive()
            && $classSession->available_spots > 0;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, BookingSession $bookingSession): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, BookingSession $bookingSession): bool
    {
        return false;
    }

    public function delete(User $user, BookingSession $bookingSession): bool
    {
        return false;
    }

    public function restore(User $user, BookingSession $bookingSession): bool
    {
        return false;
    }

    public function forceDelete(User $user, BookingSession $bookingSession): bool
    {
        return false;
    }
}
