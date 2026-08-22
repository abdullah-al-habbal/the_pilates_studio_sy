<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Services\Validation\ClassScheduleValidationService;
use App\Services\Validation\SessionConflictDetector;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClassSessionGenerationService
{
    public function __construct(
        private readonly ClassScheduleValidationService $validator,
        private readonly SessionDateCalculator $calculator,
        private readonly SessionConflictDetector $conflicts,
    ) {}

    /**
     * Validate, resolve the candidate dates, reject on any conflict, then insert.
     *
     * The whole thing runs in one transaction: previously this ran outside any
     * transaction and the Filament panel has database transactions disabled, so
     * a failure here left a committed class with zero sessions.
     */
    public function generate(Classes $class): void
    {
        DB::transaction(function () use ($class): void {
            $this->validator->validate($class);

            $dates = $this->datesFor($class);

            if ($dates === []) {
                throw ValidationException::withMessages([
                    'end_date' => __('dashboard.resources.classes.validation.no_sessions_generated'),
                ]);
            }

            $this->conflicts->assertNoDuplicates($dates, $class->start_time, (int) $class->id);

            $this->conflicts->assertNoConflicts(
                dates: $dates,
                startTime: $class->start_time,
                endTime: $class->end_time,
                instructorId: $class->instructor_id === null ? null : (int) $class->instructor_id,
                classId: (int) $class->id,
            );

            // NOTE: a bulk insert fires no model events, so ClassSessionObserver
            // does not run for generated rows. Nothing currently depends on it
            // (the observer only guards deletes), but anything added there will
            // need to be applied here explicitly.
            ClassSession::insert($this->rowsFor($class, $dates));
        });
    }

    /**
     * The concrete dates this class's schedule resolves to.
     *
     * Public so it can be asserted directly without touching the database.
     *
     * @return list<Carbon>
     */
    public function datesFor(Classes $class): array
    {
        if ($class->hasWeekdaySchedule()) {
            return $this->calculator->forWeekdays(
                $class->start_date,
                $class->end_date,
                $class->weekdayCases(),
            );
        }

        $interval = $class->recurrencePattern?->interval_days;

        if ($interval === null) {
            throw ValidationException::withMessages([
                'recurrence_pattern_id' => __('dashboard.resources.classes.validation.missing_pattern'),
            ]);
        }

        return $this->calculator->forInterval(
            $class->start_date,
            $class->end_date,
            (int) $interval,
        );
    }

    public function regenerate(Classes $class): void
    {
        DB::transaction(function () use ($class) {
            $this->assertRegenerable($class);

            $class->sessions()->forceDelete();
            $this->generate($class);
        });
    }

    public function assertRegenerable(Classes $class): void
    {
        if ($this->hasBookings($class)) {
            throw ValidationException::withMessages([
                'start_date' => 'This class has booked sessions. You cannot change the schedule because customers paid for these specific dates. Create a new class instead.',
            ]);
        }
    }

    public function assertCapacityValid(Classes $class, int $newTotalSpots): void
    {
        if ($this->wouldExceedCapacity($class, $newTotalSpots)) {
            throw ValidationException::withMessages([
                'total_spots' => 'Cannot reduce total spots below the number of currently reserved spots.',
            ]);
        }
    }

    public function hasBookings(Classes $class): bool
    {
        return $class->sessions()
            ->whereHas('bookingSessions')
            ->exists();
    }

    public function hasActiveBookings(Classes $class): bool
    {
        return $class->sessions()
            ->whereHas('bookingSessions', function ($q) {
                $q->where('status', '!=', BookingSessionStatusEnum::CANCELLED->value);
            })
            ->exists();
    }

    public function wouldExceedCapacity(Classes $class, int $newTotalSpots): bool
    {
        $maxReserved = $class->sessions()
            ->with(['bookingSessions' => function ($q) {
                $q->where('status', BookingSessionStatusEnum::RESERVED->value);
            }])
            ->get()
            ->max(fn ($session) => $session->bookingSessions->count());

        return ($maxReserved ?? 0) > $newTotalSpots;
    }

    /**
     * @param  list<Carbon>  $dates
     * @return list<array<string, mixed>>
     */
    private function rowsFor(Classes $class, array $dates): array
    {
        $now = now();

        return array_map(fn (Carbon $date) => [
            'class_id' => $class->id,
            'date' => $date->toDateString(),
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
            'total_spots' => $class->total_spots,
            'status' => ClassSessionStatusEnum::SCHEDULED->value,
            'created_at' => $now,
            'updated_at' => $now,
        ], $dates);
    }
}
