<?php

declare(strict_types=1);

namespace App\ValueObjects\Scheduling;

use Illuminate\Support\Carbon;

class ScheduleConflictVO
{
    public const REASON_INSTRUCTOR = 'instructor';

    public const REASON_SAME_CLASS = 'same_class';

    /**
     * One of the two classes has no instructor, so it is treated as occupying
     * the studio for that window.
     */
    public const REASON_STUDIO = 'studio';

    public function __construct(
        public readonly string $date,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly int $classId,
        public readonly string $classTitle,
        public readonly string $reason,
        public readonly ?int $sessionId = null,
    ) {}

    /**
     * Human-readable single-line description, used to build the validation message.
     */
    public function describe(): string
    {
        return __('dashboard.resources.classes.validation.conflict_line', [
            'date' => $this->date,
            'start' => Carbon::parse($this->startTime)->format('g:i A'),
            'end' => Carbon::parse($this->endTime)->format('g:i A'),
            'class' => $this->classTitle,
            'reason' => __('dashboard.resources.classes.validation.conflict_reason_' . $this->reason),
        ]);
    }
}
