<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Models\Classes;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final readonly class ClassScheduleValidationService
{
    public function validate(Classes $class): void
    {
        $pattern = $class->recurrencePattern;

        if (! $pattern || $pattern->interval_days <= 0) {
            throw ValidationException::withMessages([
                'recurrence_pattern_id' => 'Recurrence pattern is required.',
            ]);
        }

        $this->assertValidWindow(
            $class->start_date,
            $class->end_date,
            $pattern->interval_days,
        );
    }

    public function assertValidWindow(mixed $startDate, mixed $endDate, int $intervalDays): void
    {
        if ($startDate === null || $endDate === null) {
            return;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be after start date.',
            ]);
        }

        if ($start->diffInDays($end) < $intervalDays) {
            $minimumEnd = $start->copy()->addDays($intervalDays)->toDateString();

            throw ValidationException::withMessages([
                'end_date' => sprintf(
                    'The date range must span at least one %d-day interval (end date must be on or after %s).',
                    $intervalDays,
                    $minimumEnd
                ),
            ]);
        }
    }
}
