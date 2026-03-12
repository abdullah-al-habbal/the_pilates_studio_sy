<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RecurrencePatternFactory extends Factory
{
    public function definition(): array
    {
        $patterns = [
            ['name' => 'daily',    'label' => 'Daily',     'interval_days' => 1],
            ['name' => 'weekly',   'label' => 'Weekly',    'interval_days' => 7],
            ['name' => 'biweekly', 'label' => 'Bi-Weekly', 'interval_days' => 14],
            ['name' => 'monthly',  'label' => 'Monthly',   'interval_days' => 30],
        ];

        $pattern = $this->faker->unique()->randomElement($patterns);

        return $pattern;
    }
}
