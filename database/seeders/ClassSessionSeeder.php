<?php

namespace Database\Seeders;

use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClassSessionSeeder extends Seeder
{
    public function run(): void
    {
        Classes::with('recurrencePattern')->get()->each(function (Classes $class) {

            if ($class->recurrencePattern) {
                $this->generateFromRecurrence($class);
            } else {
                ClassSession::firstOrCreate(
                    [
                        'class_id'   => $class->id,
                        'date'       => $class->start_date->format('Y-m-d'),
                        'start_time' => $class->start_time,
                    ],
                    [
                        'end_time'    => $class->end_time,
                        'total_spots' => $class->total_spots,
                        'status'      => ClassSessionStatusEnum::SCHEDULED->value,
                    ]
                );
            }
        });
    }

    private function generateFromRecurrence(Classes $class): void
    {
        $pattern   = $class->recurrencePattern;
        $current   = Carbon::parse($class->start_date);
        $endDate   = $class->end_date
            ? Carbon::parse($class->end_date)
            : $current->copy()->addMonths(3);

        $limit = 0;

        while ($current->lte($endDate) && $limit < 100) {
            ClassSession::firstOrCreate(
                [
                    'class_id'   => $class->id,
                    'date'       => $current->format('Y-m-d'),
                    'start_time' => $class->start_time,
                ],
                [
                    'end_time'    => $class->end_time,
                    'total_spots' => $class->total_spots,
                    'status'      => $current->isPast() ? ClassSessionStatusEnum::COMPLETED->value : ClassSessionStatusEnum::SCHEDULED->value,
                ]
            );

            $current->addDays($pattern->interval_days);
            $limit++;
        }
    }
}
