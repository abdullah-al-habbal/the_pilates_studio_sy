<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Models\Booking;
use App\Services\Booking\BookingFreezeService;

final readonly class UnfreezeBookingHandler
{
    public function __construct(
        private BookingFreezeService $freezeService
    ) {}

    /**
     * Unfreeze a booking and return the new replacement booking.
     */
    public function handle(int $bookingId): Booking
    {
        $booking = Booking::findOrFail($bookingId);
        return $this->freezeService->unfreeze($booking);
    }
}
