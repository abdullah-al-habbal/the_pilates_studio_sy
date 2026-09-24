<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecurrenceUnitEnum;
use App\Models\RecurrencePattern;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(RecurrencePattern::class)]
class RecurrencePatternFactory extends Factory
{
    public function definition(): array
    {
        $patterns = [
            [
                'name' => 'daily',
                'label' => 'Daily',
                'interval_days' => 1,
                'frequency_unit' => RecurrenceUnitEnum::DAY,
                'frequency_interval' => 1,
            ],
            [
                'name' => 'weekly',
                'label' => 'Weekly',
                'interval_days' => 7,
                'frequency_unit' => RecurrenceUnitEnum::WEEK,
                'frequency_interval' => 1,
            ],
            [
                'name' => 'biweekly',
                'label' => 'Every Two Weeks',
                'interval_days' => 14,
                'frequency_unit' => RecurrenceUnitEnum::WEEK,
                'frequency_interval' => 2,
            ],
            [
                'name' => 'monthly',
                'label' => 'Monthly',
                'interval_days' => 30,
                'frequency_unit' => RecurrenceUnitEnum::MONTH,
                'frequency_interval' => 1,
            ],
        ];

        $pattern = $this->faker->unique()->randomElement($patterns);

        return $pattern;
    }
}
