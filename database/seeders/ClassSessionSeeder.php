<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use DomainException;

class ClassSessionSeeder extends Seeder
{
    public function run(): void
    {
        Classes::query()
            ->with('recurrencePattern')
            ->chunkById(50, function ($classes): void {
                /** @var Classes $class */
                foreach ($classes as $class) {
                    if ($class->recurrencePattern) {
                        $this->generateFromRecurrence($class);
                    } else {
                        $this->createSingleSession($class);
                    }
                }
            });
    }

    private function createSingleSession(Classes $class): void
    {
        ClassSession::insertOrIgnore([
            [
                'class_id' => $class->id,
                'date' => $class->start_date->format('Y-m-d'),
                'start_time' => $class->start_time,
                'end_time' => $class->end_time,
                'total_spots' => $class->total_spots,
                'status' => ClassSessionStatusEnum::SCHEDULED->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    private function generateFromRecurrence(Classes $class): void
    {
        $pattern = $class->recurrencePattern;

        if (!$pattern || $pattern->interval_days <= 0) {
            return;
        }

        $startDate = $class->start_date->copy();

        $endDate = $class->end_date
            ? $class->end_date->copy()
            : $startDate->copy()->addMonths(3);

        $dates = $this->generateDates($startDate, $endDate, $pattern->interval_days);

        if ($dates->isEmpty()) {
            return;
        }

        $rows = $dates->map(function (Carbon $date) use ($class) {
            return [
                'class_id' => $class->id,
                'date' => $date->format('Y-m-d'),
                'start_time' => $class->start_time,
                'end_time' => $class->end_time,
                'total_spots' => $class->total_spots,
                'status' => $date->isPast()
                    ? ClassSessionStatusEnum::COMPLETED->value
                    : ClassSessionStatusEnum::SCHEDULED->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        ClassSession::insertOrIgnore($rows);
    }

    private function generateDates(Carbon $start, Carbon $end, int $intervalDays): Collection
    {
        $dates = collect();
        $current = $start->copy();

        $maxIterations = 500;
        $iterations = 0;

        while ($current->lte($end) && $iterations < $maxIterations) {
            $dates->push($current->copy());

            $current->addDays($intervalDays);
            $iterations++;
        }

        return $dates;
    }
}