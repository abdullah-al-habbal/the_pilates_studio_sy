<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Enums\BookingStatusEnum;

final readonly class AssignPackageHandler
{
    public function __construct(
        private BookingService $bookingService
    ) {
    }

    public function handle(int $userId, int $packageId): Booking
    {
        $user = User::findOrFail($userId);
        $package = Package::findOrFail($packageId);

        $expiresAt = $package->validity_days ? now()->addDays($package->validity_days) : null;

        return $this->bookingService->createFromPackage($user, $package, $expiresAt);
    }
}
