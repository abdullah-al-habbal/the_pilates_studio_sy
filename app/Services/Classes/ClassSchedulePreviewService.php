<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\WeekdayEnum;
use App\Models\RecurrencePattern;
use App\Services\Validation\ClassScheduleValidationService;
use App\Services\Validation\SessionConflictDetector;
use App\ValueObjects\Scheduling\ScheduleConflictVO;
use Illuminate\Validation\ValidationException;

final readonly class ClassSchedulePreviewService
{
    private const DATE_PREVIEW_LIMIT = 12;

    private const CONFLICT_PREVIEW_LIMIT = 20;

    public function __construct(
        private SessionDateCalculator $calculator,
        private ClassScheduleValidationService $validator,
        private SessionConflictDetector $conflicts,
    ) {}

    /**
     * Preview the exact dates and conflicts that final class persistence will use.
     *
     * @param  array<mixed>  $weekdays
     *
     * @return array{
     *     count: int,
     *     dates: list<string>,
     *     last_date: string,
     *     has_more_dates: bool,
     *     conflict_count: int,
     *     conflicts: list<array{
     *         date: string,
     *         start_time: string,
     *         end_time: string,
     *         class_id: int,
     *         class_title: string,
     *         session_id: int|null,
     *         reason: string,
     *         description: string
     *     }>,
     *     has_more_conflicts: bool
     * }
     */
    public function preview(
        mixed $startDate,
        mixed $endDate,
        mixed $startTime,
        mixed $endTime,
        array $weekdays,
        ?RecurrencePattern $pattern,
        ?int $instructorId,
        ?int $classId = null,
    ): array {
        $weekdayCases = WeekdayEnum::normalise($weekdays);

        $this->validator->assertExactlyOneMode(
            $pattern?->getKey() === null ? null : (int) $pattern->getKey(),
            $weekdayCases,
        );
        $this->validator->assertValidTimes($startTime, $endTime);
        $this->validator->assertEndDatePresent($endDate);
        $this->validator->assertValidWindow(
            $startDate,
            $endDate,
            $pattern?->interval_days,
            $pattern?->resolvedFrequencyUnit(),
            $pattern?->resolvedFrequencyInterval(),
        );

        $dates = $weekdayCases !== []
            ? $this->calculator->forWeekdays($startDate, $endDate, $weekdayCases)
            : $this->calculator->forRecurrence(
                $startDate,
                $endDate,
                $pattern->resolvedFrequencyUnit(),
                $pattern->resolvedFrequencyInterval(),
            );

        if ($dates === []) {
            throw ValidationException::withMessages([
                'end_date' => __('dashboard.resources.classes.validation.no_sessions_generated'),
            ]);
        }

        $conflicts = $this->conflicts->detect(
            dates: $dates,
            startTime: $startTime,
            endTime: $endTime,
            instructorId: $instructorId,
            classId: $classId,
        );

        return [
            'count' => count($dates),
            'dates' => collect($dates)
                ->take(self::DATE_PREVIEW_LIMIT)
                ->map(fn ($date): string => $date->toDateString())
                ->values()
                ->all(),
            'last_date' => end($dates)->toDateString(),
            'has_more_dates' => count($dates) > self::DATE_PREVIEW_LIMIT,
            'conflict_count' => count($conflicts),
            'conflicts' => collect($conflicts)
                ->take(self::CONFLICT_PREVIEW_LIMIT)
                ->map(fn (ScheduleConflictVO $conflict): array => [
                    'date' => $conflict->date,
                    'start_time' => $conflict->startTime,
                    'end_time' => $conflict->endTime,
                    'class_id' => $conflict->classId,
                    'class_title' => $conflict->classTitle,
                    'session_id' => $conflict->sessionId,
                    'reason' => $conflict->reason,
                    'description' => $conflict->describe(),
                ])
                ->values()
                ->all(),
            'has_more_conflicts' => count($conflicts) > self::CONFLICT_PREVIEW_LIMIT,
        ];
    }
}
