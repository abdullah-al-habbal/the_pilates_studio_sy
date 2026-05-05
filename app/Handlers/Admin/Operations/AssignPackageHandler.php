<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Enums\BookingStatusEnum;

use Illuminate\Validation\ValidationException;

final readonly class AssignPackageHandler
{
    public function __construct(
        private BookingService $bookingService
    ) {
    }

    public function handle(int $userId, int $packageId, ?int $currencyId = null, ?int $paidAmount = null): Booking
    {
        $user = User::findOrFail($userId);

        if ($user->isFrozen()) {
            throw ValidationException::withMessages([
                'user_id' => 'Cannot assign package to a frozen account.',
            ]);
        }

        if ($user->bookings()->where('status', BookingStatusEnum::FROZEN)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Please unfreeze the existing package first.',
            ]);
        }

        $package = Package::findOrFail($packageId);

        $expiresAt = $package->validity_days ? now()->addDays($package->validity_days) : null;

        return $this->bookingService->createFromPackage($user, $package, $expiresAt, $currencyId, $paidAmount);
    }
}
