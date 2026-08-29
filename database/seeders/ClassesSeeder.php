<?php

namespace Database\Seeders;

use App\Enums\ClassStatusEnum;
use App\Enums\WeekdayEnum;
use App\Exceptions\SeederDependencyMissingException;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use Illuminate\Database\Seeder;

class ClassesSeeder extends Seeder
{
    public function run(): void
    {
        $sarahId = Instructor::where('name->en', 'Sarah Jrame')->value('id');
        $adamId = Instructor::where('name->en', 'Adam Kim')->value('id');
        $emmaId = Instructor::where('name->en', 'Emma Wall')->value('id');

        $reformerId = ClassCategory::where('name->en', 'Reformer')->value('id');
        $matId = ClassCategory::where('name->en', 'Mat')->value('id');
        $towerId = ClassCategory::where('name->en', 'Tower')->value('id');

        $weeklyId = RecurrencePattern::where('name', 'weekly')->value('id');
        $biweeklyId = RecurrencePattern::where('name', 'biweekly')->value('id');

        if (! $sarahId || ! $adamId || ! $emmaId) {
            throw new SeederDependencyMissingException('Instructor seed dependency missing.');
        }

        if (! $reformerId || ! $matId || ! $towerId) {
            throw new SeederDependencyMissingException('ClassCategory seed dependency missing.');
        }

        if (! $weeklyId || ! $biweeklyId) {
            throw new SeederDependencyMissingException('RecurrencePattern seed dependency missing.');
        }

        /*
         * Keep the seeded classes relative to the current date.
         *
         * The schedule starts several months in the past and ends one month
         * in the past, giving ClassSessionSeeder enough historical sessions
         * for testing Record Past Purchase.
         */
        $startDate = now()->subMonths(7)->startOfDay();
        $endDate = now()->subMonth()->endOfMonth()->startOfDay();

        $fixed = [

            [
                'instructor_id' => $sarahId,
                'class_category_id' => $reformerId,
                'recurrence_pattern_id' => $weeklyId,
                'weekdays' => null,
                'title' => [
                    'en' => 'Reformer Flow',
                    'ar' => 'تدفق الريفورمر',
                ],
                'about' => [
                    'en' => 'A dynamic flow focusing on core strength and fluid movement. Perfect for intermediate practitioners.',
                    'ar' => 'تمرين ديناميكي يركز على تقوية الجذع والحركة السلسة.',
                ],
                'start_time' => '08:00:00',
                'end_time' => '08:50:00',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_spots' => 8,
                'status' => ClassStatusEnum::ACTIVE->value,
            ],

            [
                'instructor_id' => $adamId,
                'class_category_id' => $matId,
                // Weekday mode: runs only on Sundays and Wednesdays.
                'recurrence_pattern_id' => null,
                'weekdays' => [WeekdayEnum::SUNDAY->value, WeekdayEnum::WEDNESDAY->value],
                'title' => [
                    'en' => 'Mat Essentials',
                    'ar' => 'أساسيات المات',
                ],
                'about' => [
                    'en' => 'Foundational mat class covering core Pilates principles. Ideal for all levels.',
                    'ar' => 'حصة تأسيسية تغطي مبادئ البيلاتس الأساسية.',
                ],
                'start_time' => '09:30:00',
                'end_time' => '10:15:00',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_spots' => 8,
                'status' => ClassStatusEnum::ACTIVE->value,
            ],

            [
                'instructor_id' => $emmaId,
                'class_category_id' => $towerId,
                'recurrence_pattern_id' => $biweeklyId,
                'weekdays' => null,
                'title' => [
                    'en' => 'Tower Power',
                    'ar' => 'قوة التاور',
                ],
                'about' => [
                    'en' => 'High-intensity tower workout combining strength and flexibility.',
                    'ar' => 'تمرين عالي الكثافة يجمع بين القوة والمرونة.',
                ],
                'start_time' => '08:00:00',
                'end_time' => '08:50:00',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_spots' => 10,
                'status' => ClassStatusEnum::ACTIVE->value,
            ],
        ];

        Classes::withoutEvents(function () use ($fixed): void {
            foreach ($fixed as $class) {
                Classes::firstOrCreate(
                    [
                        'title->en' => $class['title']['en'],
                        'start_date' => $class['start_date'],
                    ],
                    $class
                );
            }

            Classes::factory(7)->create();
            Classes::factory(3)->interval()->create();
            Classes::factory(3)->inactive()->create();
        });
    }
}
