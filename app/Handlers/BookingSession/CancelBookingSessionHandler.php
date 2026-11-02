<?php

declare(strict_types=1);

namespace App\Handlers\BookingSession;

use App\Commands\BookingSession\CancelBookingSessionCommand;
use App\Models\BookingSession;
use App\Models\User;
use App\Services\BookingSession\BookingSessionService;

final readonly class CancelBookingSessionHandler
{
    public function __construct(
        private BookingSessionService $bookingSessions,
    ) {}

    public function handle(CancelBookingSessionCommand $command): BookingSession
    {
        $actor = User::query()->findOrFail($command->actorId);

        return $this->bookingSessions->cancel(
            bookingSessionId: $command->bookingSessionId,
            actor: $actor,
            adminOverride: $command->isAdminOverride,
            reason: $command->reason,
        );
    }
}
