<?php
declare(strict_types=1);
namespace App\Handlers\User;
use App\Enums\BookingStatusEnum;
use App\Enums\PackageTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
class UnfreezeUserHandler
{
    public function __construct(
        private readonly CreateBookingFromPackageHandler $createBookingHandler
    ) {}
    /**
     * Unfreeze a user: create pro-rated package + booking from frozen snapshot
     */
    public function handle(User $user): void
    {
        if (! $user->isFrozen()) {
            throw new InvalidArgumentException('User is not frozen.');
        }
        $frozenBooking = $user->bookings()
            ->where('status', BookingStatusEnum::FROZEN)
            ->latest('id')
            ->first();
        if (! $frozenBooking) {
            throw new InvalidArgumentException('Cannot unfreeze: no frozen booking found.');
        }
        DB::transaction(function () use ($user, $frozenBooking) {
            $originalExpires = $frozenBooking->expires_at;
            $frozenAt = $user->frozen_at;

            if (! $originalExpires || ! $frozenAt) {
                throw new InvalidArgumentException('Missing snapshot data for unfreeze.');
            }
            $residualDays = max(1, (int) floor($originalExpires->diffInHours($frozenAt) / 24));
            $remainingCredits = $frozenBooking->remaining_credits;
            // Create system-generated pro-rated package
            $package = Package::create([
                'name' => ['en' => "Unfreeze Residual - {$user->fullname}", 'ar' => "رصيد متبقي - {$user->fullname}"],
                'total_credits' => $remainingCredits,
                'price' => 0,
                'type' => PackageTypeEnum::FOR_FREEZE_CLIENT,
                'generated_reason' => "unfreeze residual from booking #{$frozenBooking->id}",
                'validity_days' => $residualDays,
                'is_active' => false,
            ]);
            // Create new active booking from package
            ($this->createBookingHandler)(
                user: $user,
                package: $package,
                expiresAt: now()->addDays($residualDays)
            );
            // Reactivate user
            $user->update([
                'status' => UserStatusEnum::ACTIVE,
                'frozen_at' => null,
                'freeze_reason' => null,
            ]);
        });
    }
}
