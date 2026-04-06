<?php

// app/Listeners/CreateInitialBookingForUserSuccessfullyRegisteredListener.php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserSuccessfullyRegisteredEvent;
use App\Services\Booking\BookingService;
use App\Services\Package\PackageService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateInitialBookingForUserSuccessfullyRegisteredListener implements ShouldQueue
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PackageService $packageService,
    ) {}

    public function handle(UserSuccessfullyRegisteredEvent $event): void
    {
        $user = $event->user;

        if ($this->bookingService->userHasActiveCreditBooking($user)) {
            Log::info('User already has active booking', ['user_id' => $user->id]);

            return;
        }

        $package = $this->packageService->getCheapestActivePackage();

        if (! $package) {
            Log::error('No active package found for auto-booking', ['user_id' => $user->id]);

            return;
        }

        $this->bookingService->createFromPackage($user, $package, Carbon::now()->addDays(10));
    }
}
