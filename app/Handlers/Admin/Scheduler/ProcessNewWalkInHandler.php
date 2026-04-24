<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Commands\Admin\Scheduler\ProcessNewWalkInCommand;
use App\Models\ClassSession;
use App\Services\BookingSession\BookingSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ProcessNewWalkInHandler
{
    public function __construct(
        private BookingSessionService $bookingSessionService,
    ) {
    }

    public function handle(ProcessNewWalkInCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $session = ClassSession::lockForUpdate()->findOrFail($command->sessionId);
            $capacity = (int) ($session->total_spots ?? 0);
            $reserved = $session->bookingSessions()->count();

            if ($capacity > 0 && $reserved >= $capacity) {
                throw ValidationException::withMessages([
                    'session' => 'This session has reached full capacity.',
                ]);
            }

            $user = $this->bookingSessionService->createWalkInUser([
                'fullname' => $command->fullname,
                'phone_number' => $command->phoneNumber,
                'email' => $command->email,
                'password' => $command->password,
            ]);

            $this->bookingSessionService->oneTimeAttend($user->id, $command->sessionId);
        });
    }
}
