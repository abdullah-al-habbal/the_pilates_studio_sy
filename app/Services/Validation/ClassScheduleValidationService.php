<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Enums\WeekdayEnum;
use App\Models\Classes;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Shape and sanity checks for a class schedule.
 *
 * Conflict detection lives in SessionConflictDetector, which needs the concrete
 * candidate dates and therefore runs inside the generation service, after this.
 */
final readonly class ClassScheduleValidationService
{
    /**
     * Full pre-generation validation. Order matters: mode first, because every
     * later rule depends on which mode is in play.
     */
    public function validate(Classes $class): void
    {
        $this->assertExactlyOneMode($class->recurrence_pattern_id, $class->weekdayCases());
        $this->assertValidTimes($class->start_time, $class->end_time);
        $this->assertEndDatePresent($class->end_date);

        $this->assertValidWindow(
            $class->start_date,
            $class->end_date,
            $class->hasWeekdaySchedule() ? null : $class->recurrencePattern?->interval_days,
        );
    }

    /**
     * A class is scheduled by weekdays OR by a recurrence interval — never both,
     * never neither. The database cannot express this (nullable FK + JSON column),
     * so it is enforced here and mirrored in the Filament form.
     *
     * @param  list<WeekdayEnum>  $weekdays
     */
    public function assertExactlyOneMode(?int $recurrencePatternId, array $weekdays): void
    {
        $hasWeekdays = $weekdays !== [];
        $hasPattern = $recurrencePatternId !== null;

        if ($hasWeekdays && $hasPattern) {
            throw ValidationException::withMessages([
                'weekdays' => __('dashboard.resources.classes.validation.both_modes'),
            ]);
        }

        if (! $hasWeekdays && ! $hasPattern) {
            throw ValidationException::withMessages([
                'weekdays' => __('dashboard.resources.classes.validation.no_mode'),
            ]);
        }
    }

    /**
     * Sessions may not cross midnight, so the end time must be strictly later
     * than the start time. Previously only the Filament form enforced this,
     * which left factories, seeders and tinker free to create negative-duration
     * sessions.
     */
    public function assertValidTimes(mixed $startTime, mixed $endTime): void
    {
        if ($startTime === null || $endTime === null) {
            return;
        }

        $start = Carbon::parse((string) $startTime)->format('H:i:s');
        $end = Carbon::parse((string) $endTime)->format('H:i:s');

        if ($end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => __('dashboard.resources.classes.validation.end_time_after_start'),
            ]);
        }
    }

    /**
     * Generation needs a bounded range. The column is nullable and the Filament
     * form marks it required, so this only fires for programmatic writes — where
     * it used to silently truncate the range to "today" instead.
     */
    public function assertEndDatePresent(mixed $endDate): void
    {
        if ($endDate === null) {
            throw ValidationException::withMessages([
                'end_date' => __('dashboard.resources.classes.validation.end_date_required'),
            ]);
        }
    }

    /**
     * Both bounds must be present for this to say anything — it is also called
     * live from the Filament form while the admin is still filling fields in.
     *
     * $intervalDays is null in weekday mode, where the minimum-span rule makes
     * no sense.
     */
    public function assertValidWindow(mixed $startDate, mixed $endDate, ?int $intervalDays): void
    {
        if ($startDate === null || $endDate === null) {
            return;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        // A single-day range is legal: in weekday mode it yields one session if
        // that day matches, and zero otherwise (reported separately).
        if ($start->greaterThan($end)) {
            throw ValidationException::withMessages([
                'end_date' => __('dashboard.resources.classes.validation.end_before_start'),
            ]);
        }

        if ($intervalDays === null) {
            return;
        }

        if ($intervalDays <= 0) {
            throw ValidationException::withMessages([
                'recurrence_pattern_id' => __('dashboard.resources.classes.validation.invalid_interval'),
            ]);
        }

        if ($start->diffInDays($end) < $intervalDays) {
            throw ValidationException::withMessages([
                'end_date' => __('dashboard.resources.classes.validation.window_too_short', [
                    'days' => $intervalDays,
                    'date' => $start->copy()->addDays($intervalDays)->toDateString(),
                ]),
            ]);
        }
    }
}
