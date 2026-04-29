<?php

declare(strict_types=1);

namespace App\Handlers\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;

class CreateBookingFromPackageHandler
{

    public function __invoke(User $user, Package $package, ?Carbon $expiresAt = null): Booking
    {
        $expiresAt = $expiresAt ?? ($package->validity_days ? now()->addDays($package->validity_days) : null);

        return Booking::create([
            'user_id'           => $user->id,
            'package_id'        => $package->id,
            'total_credits'     => $package->total_credits,
            'remaining_credits' => $package->total_credits,
            'status'            => BookingStatusEnum::ACTIVE,
            'expires_at'        => $expiresAt,
        ]);
    }
}
