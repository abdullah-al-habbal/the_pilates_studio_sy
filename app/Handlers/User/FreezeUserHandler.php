<?php
declare(strict_types=1);
namespace App\Handlers\User;
use App\Enums\BookingStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
class FreezeUserHandler
{
    /**
     * Freeze a user: snapshot their active booking, mark user+booking as frozen
     */
    public function handle(User $user, string $reason): void
    {
        if ($user->isFrozen()) {
            throw new InvalidArgumentException('User is already frozen.');
        }
        $activeBooking = $user->activeCreditBooking;
        if (! $activeBooking) {
            throw new InvalidArgumentException('Cannot freeze: user has no active booking with credits.');
        }
        DB::transaction(function () use ($user, $reason, $activeBooking) {
            // Freeze the booking (preserve history with snapshot data)
            $activeBooking->update([
                'status' => BookingStatusEnum::FROZEN,
                // Optional: store snapshot in JSON if you add a freeze_snapshot column
            ]);
            // Freeze the user
            $user->update([
                'status' => UserStatusEnum::FROZEN,
                'frozen_at' => now(),
                'freeze_reason' => $reason,
            ]);
        });
    }
}
