<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Services\Classes\SessionDateCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;


class ClassSessionSeeder extends Seeder
{
    public function __construct(
        private readonly SessionDateCalculator $calculator = new SessionDateCalculator
    ) {}

    public function run(): void
    {
        Classes::query()
            ->with('recurrencePattern')
            ->chunkById(50, function ($classes): void {
                /** @var Classes $class */
                foreach ($classes as $class) {
                    $dates = $this->datesFor($class);

                    if ($dates === []) {
                        $this->createSingleSession($class);

                        continue;
                    }

                    $this->insertSessions($class, $dates);
                }
            });
    }

    private function datesFor(Classes $class): array
    {
        try {
            $end = $class->end_date?->copy() ?? $class->start_date->copy()->addMonths(3);

            if ($class->hasWeekdaySchedule()) {
                return $this->calculator->forWeekdays($class->start_date, $end, $class->weekdayCases());
            }

            $interval = $class->recurrencePattern?->interval_days;

            if ($interval === null || $interval <= 0) {
                return [];
            }

            return $this->calculator->forInterval($class->start_date, $end, (int) $interval);
        } catch (\Throwable) {
            return [];
        }
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
            ],
        ]);
    }

    private function insertSessions(Classes $class, array $dates): void
    {
        $rows = array_map(fn (Carbon $date) => [
            'class_id' => $class->id,
            'date' => $date->toDateString(),
            'start_time' => $class->start_time,
            'end_time' => $class->end_time,
            'total_spots' => $class->total_spots,
            'status' => $date->isPast()
                ? ClassSessionStatusEnum::COMPLETED->value
                : ClassSessionStatusEnum::SCHEDULED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ], $dates);

        ClassSession::insertOrIgnore($rows);
    }
}
