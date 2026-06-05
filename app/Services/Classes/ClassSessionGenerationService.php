<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ClassSessionGenerationService
{
    public function generate(Classes $class): void
    {
        $pattern = $class->recurrencePattern;

        if (!$pattern || $pattern->interval_days <= 0) {
            throw new InvalidArgumentException('Valid recurrence pattern with interval > 0 is required.');
        }

        $start = Carbon::parse($class->start_date)->startOfDay();
        $end   = Carbon::parse($class->end_date)->endOfDay();
        $interval = $pattern->interval_days;

        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException('End date must be on or after start date.');
        }

        $rows = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $rows[] = [
                'class_id'    => $class->id,
                'date'        => $cursor->toDateString(),
                'start_time'  => $class->start_time,
                'end_time'    => $class->end_time,
                'total_spots' => $class->total_spots,
                'status'      => ClassSessionStatusEnum::SCHEDULED->value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            $cursor->addDays($interval);
        }

        if (!empty($rows)) {
            ClassSession::insertOrIgnore($rows);
        }
    }

    public function regenerate(Classes $class): void
    {
        DB::transaction(function () use ($class) {
            $class->sessions()->forceDelete();
            $this->generate($class);
        });
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
