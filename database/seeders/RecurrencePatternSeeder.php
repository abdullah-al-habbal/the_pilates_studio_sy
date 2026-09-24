<?php

namespace Database\Seeders;

use App\Enums\RecurrenceUnitEnum;
use App\Models\RecurrencePattern;
use Illuminate\Database\Seeder;

class RecurrencePatternSeeder extends Seeder
{
    public function run(): void
    {
        $patterns = [
            [
                'name' => 'daily',
                'label' => ['en' => 'Daily', 'ar' => 'يومي'],
                'interval_days' => 1,
                'frequency_unit' => RecurrenceUnitEnum::DAY,
                'frequency_interval' => 1,
            ],
            [
                'name' => 'weekly',
                'label' => ['en' => 'Weekly', 'ar' => 'أسبوعي'],
                'interval_days' => 7,
                'frequency_unit' => RecurrenceUnitEnum::WEEK,
                'frequency_interval' => 1,
            ],
            [
                'name' => 'biweekly',
                'label' => ['en' => 'Every Two Weeks', 'ar' => 'كل أسبوعين'],
                'interval_days' => 14,
                'frequency_unit' => RecurrenceUnitEnum::WEEK,
                'frequency_interval' => 2,
            ],
            [
                'name' => 'monthly',
                'label' => ['en' => 'Monthly', 'ar' => 'شهري'],
                'interval_days' => 30,
                'frequency_unit' => RecurrenceUnitEnum::MONTH,
                'frequency_interval' => 1,
            ],
        ];

        foreach ($patterns as $pattern) {
            RecurrencePattern::firstOrCreate(['name' => $pattern['name']], $pattern);
        }
    }
}
