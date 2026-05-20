<?php

declare(strict_types=1);

namespace App\Support\Operations;

use App\Enums\BookingStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;

final class ClientDisplayStatusResolver
{
    public static function resolve(User $user): string
    {
        if ($user->status !== UserStatusEnum::ACTIVE) {
            return $user->status->value;
        }

        if (self::hasFrozenBooking($user)) {
            return 'frozen';
        }

        return UserStatusEnum::ACTIVE->value;
    }

    private static function hasFrozenBooking(User $user): bool
    {
        if ($user->relationLoaded('bookings')) {
            return $user->bookings->contains(
                fn ($booking) => $booking->status === BookingStatusEnum::FROZEN
            );
        }

        if ($user->relationLoaded('frozenCreditBooking') && $user->frozenCreditBooking !== null) {
            return true;
        }

        return $user->bookings()->where('status', BookingStatusEnum::FROZEN)->exists();
    }
}
