<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Booking;
use App\Services\Booking\BookingFreezeService;

final readonly class FreezeBookingHandler
{
    public function __construct(
        private BookingFreezeService $freezeService
    ) {
    }

    public function handle(int $bookingId): void
    {
        $booking = Booking::findOrFail($bookingId);
        $this->freezeService->freeze($booking);
    }
}
