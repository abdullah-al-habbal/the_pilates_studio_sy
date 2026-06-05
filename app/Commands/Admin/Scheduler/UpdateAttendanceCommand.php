<?php
declare(strict_types=1);

namespace App\Commands\Admin\Scheduler;

use App\Enums\AttendanceStatusEnum;

final readonly class UpdateAttendanceCommand
{
    public function __construct(
        public int $classSessionId,
        public int $bookingSessionId,
        public AttendanceStatusEnum $status,
        public ?int $updatedByAdminId = null,
    ) {
    }
}
