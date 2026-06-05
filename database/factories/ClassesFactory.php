<?php

namespace Database\Factories;

use App\Enums\ClassStatusEnum;
use App\Models\ClassCategory;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassesFactory extends Factory
{
    public function definition(): array
    {
        $startHour = $this->faker->randomElement([7, 8, 9, 10, 17, 18, 19]);
        $titleEn   = $this->faker->randomElement([
            'Reformer Flow',
            'Mat Essentials',
            'Tower Power',
            'Barre Burn',
            'Core Fusion',
            'Stretch & Recover',
        ]);
        $startDate = $this->faker->dateTimeBetween('now', '+60 days');

        return [
            'instructor_id'         => Instructor::inRandomOrder()->first()?->id ?? Instructor::factory(),
            'class_category_id'     => ClassCategory::inRandomOrder()->first()?->id ?? ClassCategory::factory(),
            'recurrence_pattern_id' => RecurrencePattern::inRandomOrder()->value('id')
                ?? RecurrencePattern::factory()->create()->id,
            'title'                 => ['en' => $titleEn, 'ar' => $titleEn],
            'about'                 => ['en' => $this->faker->sentences(2, true), 'ar' => ''],
            'start_time'            => sprintf('%02d:00:00', $startHour),
            'end_time'              => sprintf('%02d:50:00', $startHour),
            'start_date'            => $startDate->format('Y-m-d'),
            'end_date'              => $this->faker->optional(0.7)
                ->dateTimeBetween($startDate, '+6 months')
                ?->format('Y-m-d'),
            'total_spots'           => $this->faker->randomElement([6, 8, 10, 12]),
            'status'                => ClassStatusEnum::ACTIVE->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ClassStatusEnum::INACTIVE->value,
        ]);
    }
}
