<?php

// app/ValueObjects/User/UserProfileWithBooking.php

declare(strict_types=1);

namespace App\ValueObjects\User;

use App\Models\Booking;
use App\Models\User;

final readonly class UserProfileWithBooking
{
    public function __construct(
        private User $user,
        private ?Booking $activeBooking = null,
    ) {}

    public static function fromUser(User $user): self
    {
        $activeBooking = $user->relationLoaded('activeCreditBooking')
            ? $user->activeCreditBooking
            : $user->activeCreditBooking;

        return new self($user, $activeBooking);
    }

    public function toArray(): array
    {
        $dateOfBirth = ($this->user->date_of_birth && method_exists($this->user->date_of_birth, 'toDateString'))
            ? $this->user->date_of_birth->toDateString()
            : null;

        $data = [
            'id' => $this->user->id,
            'fullname' => $this->user->fullname,
            'email' => $this->user->email,
            'phone_number' => $this->user->phone_number,
            'date_of_birth' => $dateOfBirth,
            'email_verified' => $this->user->is_verified,
            'is_deactivated' => $this->user->is_deactivated,
            'has_credits' => $this->user->has_credits,
            'can_book_new_package' => $this->user->can_book_new_package,
            'can_reserve_class' => $this->user->can_reserve_class,
            'has_active_booking' => $this->user->has_active_booking,
            'total_remaining_credits' => $this->user->total_remaining_credits,
        ];

        if ($this->activeBooking) {
            $package = $this->activeBooking->package;

            $data['booking_id'] = $this->activeBooking->id;
            $data['booking_status'] = $this->activeBooking->status->value;
            $data['booking_remaining_credits'] = $this->activeBooking->remaining_credits;
            $data['booking_expires_at'] = $this->activeBooking->expires_at?->toISOString();
            $data['booking_has_credits_remaining'] = $this->activeBooking->has_credits_remaining;
            $data['booking_can_be_cancelled'] = $this->activeBooking->can_be_cancelled;
            $data['booking_is_exhausted'] = $this->activeBooking->is_exhausted;
            $data['booking_credits_near_empty'] = $this->activeBooking->credits_near_empty;

            if ($package) {
                $data['package_id'] = $package->id;
                $data['package_total_credits'] = $package->total_credits;
            } else {
                $data['package_id'] = null;
                $data['package_total_credits'] = null;
            }
        } else {
            $data['booking_id'] = null;
            $data['booking_status'] = null;
            $data['booking_remaining_credits'] = null;
            $data['booking_expires_at'] = null;
            $data['booking_has_credits_remaining'] = null;
            $data['booking_can_be_cancelled'] = null;
            $data['booking_is_exhausted'] = null;
            $data['booking_credits_near_empty'] = null;
            $data['package_id'] = null;
            $data['package_total_credits'] = null;
        }

        return $data;
    }
}
