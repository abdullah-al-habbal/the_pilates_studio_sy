<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\WeekdayEnum;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Turns a schedule definition into the concrete list of dates to generate.
 *
 * Deliberately pure: no database, no Eloquent, no clock. Everything the two
 * recurrence modes disagree about lives here and nowhere else.
 */
final readonly class SessionDateCalculator
{
    /**
     * Upper bound on a single generation run. Guards against a typo in the end
     * date turning into tens of thousands of rows.
     */
    public const MAX_SESSIONS = 500;

    /**
     * Dates falling on the selected weekdays, inclusive of both bounds.
     *
     * @param  list<WeekdayEnum>  $weekdays
     * @return list<Carbon>
     */
    public function forWeekdays(mixed $startDate, mixed $endDate, array $weekdays): array
    {
        if ($weekdays === []) {
            throw new InvalidArgumentException('At least one weekday is required.');
        }

        [$start, $end] = $this->normaliseRange($startDate, $endDate);

        $dates = [];

        foreach ($weekdays as $weekday) {
            // Jump straight to the first matching date rather than walking every
            // day in the range, then stride a week at a time.
            $cursor = $start->copy();
            $offset = ($weekday->carbonDayOfWeek() - $cursor->dayOfWeek + 7) % 7;
            $cursor->addDays($offset);

            while ($cursor->lessThanOrEqualTo($end)) {
                $dates[$cursor->toDateString()] = $cursor->copy();
                $cursor->addDays(7);
            }
        }

        ksort($dates);

        return $this->guardVolume(array_values($dates));
    }

    /**
     * Dates on a fixed N-day stride from the start date, inclusive of both bounds.
     *
     * @return list<Carbon>
     */
    public function forInterval(mixed $startDate, mixed $endDate, int $intervalDays): array
    {
        if ($intervalDays <= 0) {
            throw new InvalidArgumentException('Interval days must be greater than zero.');
        }

        [$start, $end] = $this->normaliseRange($startDate, $endDate);

        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $dates[] = $cursor->copy();
            $cursor->addDays($intervalDays);
        }

        return $this->guardVolume($dates);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function normaliseRange(mixed $startDate, mixed $endDate): array
    {
        if ($startDate === null) {
            throw new InvalidArgumentException('A start date is required.');
        }

        if ($endDate === null) {
            throw new InvalidArgumentException('An end date is required.');
        }

        // Both bounds are compared as whole days: a session on the end date
        // itself is included, regardless of its time of day.
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('End date must not be before start date.');
        }

        return [$start, $end];
    }

    /**
     * @param  list<Carbon>  $dates
     * @return list<Carbon>
     */
    private function guardVolume(array $dates): array
    {
        if (count($dates) > self::MAX_SESSIONS) {
            throw ValidationException::withMessages([
                'end_date' => __('dashboard.resources.classes.validation.too_many_sessions', [
                    'count' => count($dates),
                    'max' => self::MAX_SESSIONS,
                ]),
            ]);
        }

        return $dates;
    }
}
