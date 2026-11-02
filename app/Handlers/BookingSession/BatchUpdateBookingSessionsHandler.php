<?php

declare(strict_types=1);

namespace App\Handlers\BookingSession;

use App\Commands\BookingSession\BatchUpdateBookingSessionsCommand;
use App\Models\User;
use App\Services\BookingSession\BookingSessionService;

final readonly class BatchUpdateBookingSessionsHandler
{
    public function __construct(
        private BookingSessionService $bookingSessions,
    ) {}

    public function handle(BatchUpdateBookingSessionsCommand $command): void
    {
        $actor = User::query()->findOrFail($command->actorId);

        $this->bookingSessions->applyBatch($command->bookingId, $command->items, $actor);
    }
}
