<?php

declare(strict_types=1);

namespace App\Commands\BookingSession;

final readonly class CancelBookingSessionCommand
{
    public function __construct(
        public int $bookingSessionId,
        public int $actorId,
        public bool $isAdminOverride = false,
        public ?string $reason = null,
    ) {}
}
