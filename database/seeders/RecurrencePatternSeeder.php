<?php

namespace Database\Seeders;

use App\Models\RecurrencePattern;
use Illuminate\Database\Seeder;

class RecurrencePatternSeeder extends Seeder
{
    public function run(): void
    {
        $patterns = [
            ['name' => 'daily',    'label' => ['en' => 'Daily',     'ar' => 'يومي'],       'interval_days' => 1],
            ['name' => 'weekly',   'label' => ['en' => 'Weekly',    'ar' => 'أسبوعي'],     'interval_days' => 7],
            ['name' => 'biweekly', 'label' => ['en' => 'Bi-Weekly', 'ar' => 'كل أسبوعين'], 'interval_days' => 14],
            ['name' => 'monthly',  'label' => ['en' => 'Monthly',   'ar' => 'شهري'],       'interval_days' => 30],
        ];

        foreach ($patterns as $pattern) {
            RecurrencePattern::firstOrCreate(['name' => $pattern['name']], $pattern);
        }
    }
}
