<?php

declare(strict_types=1);

namespace App\Commands\BookingSession;

final readonly class BatchUpdateBookingSessionsCommand
{
    /**
     * @param  array<int, array{booking_session_id: int, status: string, override?: bool, reason?: string|null}>  $items
     */
    public function __construct(
        public int $bookingId,
        public int $actorId,
        public array $items,
    ) {}
}
