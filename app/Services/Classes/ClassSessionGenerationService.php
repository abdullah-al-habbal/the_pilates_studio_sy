<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Services\Validation\ClassScheduleValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClassSessionGenerationService
{
    public function __construct(
        private readonly ClassScheduleValidationService $validator
    ) {}

    public function generate(Classes $class): void
    {
        $this->validator->validate($class);

        $pattern = $class->recurrencePattern;
        $start = Carbon::parse($class->start_date)->startOfDay();
        $end = Carbon::parse($class->end_date)->endOfDay();
        $interval = $pattern->interval_days;

        $rows = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $rows[] = [
                'class_id' => $class->id,
                'date' => $cursor->toDateString(),
                'start_time' => $class->start_time,
                'end_time' => $class->end_time,
                'total_spots' => $class->total_spots,
                'status' => ClassSessionStatusEnum::SCHEDULED->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $cursor->addDays($interval);
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'end_date' => 'The selected date range did not generate any sessions.',
            ]);
        }

        ClassSession::insert($rows);
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
}
