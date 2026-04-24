<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Commands\Admin\Scheduler\UpdateAttendanceCommand;
use App\Enums\AttendanceStatusEnum;
use App\Models\BookingSession;

final readonly class UpdateAttendanceHandler
{
    public function handle(UpdateAttendanceCommand $command): void
    {
        $bookingSession = BookingSession::findOrFail($command->bookingSessionId);

        if ($bookingSession->attendance_status === $command->status) {
            return;
        }

        match ($command->status) {
            AttendanceStatusEnum::ATTENDED => $bookingSession->markAttended(),
            AttendanceStatusEnum::MISSED => $bookingSession->markMissed(),
        };
    }
}
