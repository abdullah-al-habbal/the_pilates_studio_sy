<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Commands\Admin\Scheduler\UpdateAttendanceCommand;
use App\Enums\AttendanceStatusEnum;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final readonly class UpdateAttendanceHandler
{
    public function __construct(
        private BookingSessionEloquentRepository $repository
    ) {}

    public function handle(UpdateAttendanceCommand $command): void
    {
        $bookingSession = $this->repository->find($command->bookingSessionId);

        if (! $bookingSession) {
            throw new ModelNotFoundException("Booking session with id {$command->bookingSessionId} not found.");
        }

        if ((int) $bookingSession->class_session_id !== $command->classSessionId) {
            throw ValidationException::withMessages([
                'booking_session_id' => 'This booking session does not belong to the selected class session.',
            ]);
        }

        if ($bookingSession->attendance_status === $command->status) {
            return;
        }

        match ($command->status) {
            AttendanceStatusEnum::ATTENDED => $this->repository->markAttended($command->bookingSessionId),
            AttendanceStatusEnum::MISSED => $this->repository->markMissed($command->bookingSessionId),
        };
    }
}
