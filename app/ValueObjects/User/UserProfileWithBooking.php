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
    ) {
    }

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
            'email_verified' => !is_null($this->user->email_verified_at),
            'is_active' => $this->user->isActive(),
            'total_remaining_credits' => $this->user->total_remaining_credits,
        ];

        if ($this->activeBooking) {
            $package = $this->activeBooking->package;

            $data['booking_id'] = $this->activeBooking->id;
            $data['booking_status'] = $this->activeBooking->status->value;
            $data['booking_remaining_credits'] = $this->activeBooking->remaining_credits;
            $data['booking_expires_at'] = $this->activeBooking->expires_at?->toISOString();

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
            $data['package_id'] = null;
            $data['package_total_credits'] = null;
        }

        return $data;
    }
}