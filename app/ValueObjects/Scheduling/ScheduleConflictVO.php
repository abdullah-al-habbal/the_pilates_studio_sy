<?php

declare(strict_types=1);

namespace App\ValueObjects\Scheduling;

class ScheduleConflictVO
{
    public const REASON_INSTRUCTOR = 'instructor';

    public const REASON_SAME_CLASS = 'same_class';

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
            'start' => substr($this->startTime, 0, 5),
            'end' => substr($this->endTime, 0, 5),
            'class' => $this->classTitle,
            'reason' => __('dashboard.resources.classes.validation.conflict_reason_'.$this->reason),
        ]);
    }
}
