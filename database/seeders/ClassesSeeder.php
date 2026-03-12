<?php

namespace Database\Seeders;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use App\Models\Instructor;
use App\Models\ClassCategory;
use App\Models\RecurrencePattern;
use Illuminate\Database\Seeder;
use RuntimeException;

class ClassesSeeder extends Seeder
{
    public function run(): void
    {
        $sarahId = Instructor::where('name->en', 'Sarah Jrame')->value('id');
        $adamId  = Instructor::where('name->en', 'Adam Kim')->value('id');
        $emmaId  = Instructor::where('name->en', 'Emma Wall')->value('id');

        $reformerId = ClassCategory::where('name->en', 'Reformer')->value('id');
        $matId      = ClassCategory::where('name->en', 'Mat')->value('id');
        $towerId    = ClassCategory::where('name->en', 'Tower')->value('id');

        $weeklyId   = RecurrencePattern::where('name', 'weekly')->value('id');
        $biweeklyId = RecurrencePattern::where('name', 'biweekly')->value('id');

        if (!$sarahId || !$adamId || !$emmaId) {
            throw new RuntimeException('Instructor seed dependency missing.');
        }

        if (!$reformerId || !$matId || !$towerId) {
            throw new RuntimeException('ClassCategory seed dependency missing.');
        }

        if (!$weeklyId || !$biweeklyId) {
            throw new RuntimeException('RecurrencePattern seed dependency missing.');
        }

        $fixed = [

            [
                'instructor_id' => $sarahId,
                'class_category_id' => $reformerId,
                'recurrence_pattern_id' => $weeklyId,
                'title' => [
                    'en' => 'Reformer Flow',
                    'ar' => 'تدفق الريفورمر'
                ],
                'about' => [
                    'en' => 'A dynamic flow focusing on core strength and fluid movement. Perfect for intermediate practitioners.',
                    'ar' => 'تمرين ديناميكي يركز على تقوية الجذع والحركة السلسة.'
                ],
                'start_time' => '08:00:00',
                'end_time' => '08:50:00',
                'start_date' => '2026-01-30',
                'end_date' => '2026-06-30',
                'total_spots' => 8,
                'status' => ClassStatusEnum::ACTIVE->value,
            ],

            [
                'instructor_id' => $adamId,
                'class_category_id' => $matId,
                'recurrence_pattern_id' => $weeklyId,
                'title' => [
                    'en' => 'Mat Essentials',
                    'ar' => 'أساسيات المات'
                ],
                'about' => [
                    'en' => 'Foundational mat class covering core Pilates principles. Ideal for all levels.',
                    'ar' => 'حصة تأسيسية تغطي مبادئ البيلاتس الأساسية.'
                ],
                'start_time' => '09:30:00',
                'end_time' => '10:15:00',
                'start_date' => '2026-01-30',
                'end_date' => '2026-06-30',
                'total_spots' => 8,
                'status' => ClassStatusEnum::ACTIVE->value,
            ],

            [
                'instructor_id' => $emmaId,
                'class_category_id' => $towerId,
                'recurrence_pattern_id' => $biweeklyId,
                'title' => [
                    'en' => 'Tower Power',
                    'ar' => 'قوة التاور'
                ],
                'about' => [
                    'en' => 'High-intensity tower workout combining strength and flexibility.',
                    'ar' => 'تمرين عالي الكثافة يجمع بين القوة والمرونة.'
                ],
                'start_time' => '08:00:00',
                'end_time' => '08:50:00',
                'start_date' => '2026-01-30',
                'end_date' => '2026-06-30',
                'total_spots' => 10,
                'status' => ClassStatusEnum::ACTIVE->value,
            ],
        ];

        foreach ($fixed as $class) {
            Classes::firstOrCreate(
                [
                    'title->en' => $class['title']['en'],
                    'start_date' => $class['start_date']
                ],
                $class
            );
        }

        Classes::factory(10)->create();
        Classes::factory(3)->inactive()->create();
    }
}
