<?php

namespace Database\Factories;

use App\Enums\ClassStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassesFactory extends Factory
{
    public function definition(): array
    {
        $startHour = $this->faker->randomElement([7, 8, 9, 10, 17, 18, 19]);
        $titleEn = $this->faker->randomElement([
            'Reformer Flow',
            'Mat Essentials',
            'Tower Power',
            'Barre Burn',
            'Core Fusion',
            'Stretch & Recover',
        ]);
        $startDate = $this->faker->dateTimeBetween('now', '+60 days');

        return [
            'instructor_id' => Instructor::inRandomOrder()->first()?->id ?? Instructor::factory(),
            'class_category_id' => ClassCategory::inRandomOrder()->first()?->id ?? ClassCategory::factory(),

            // Weekday mode is the default: it needs no lookup row and always
            // satisfies the schedule rules for any range where end >= start.
            'recurrence_pattern_id' => null,
            'weekdays' => $this->faker->randomElements(
                WeekdayEnum::values(),
                $this->faker->numberBetween(1, 3)
            ),

            'title' => ['en' => $titleEn, 'ar' => $titleEn],
            'about' => ['en' => $this->faker->sentences(2, true), 'ar' => ''],
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:50:00', $startHour),
            'start_date' => $startDate->format('Y-m-d'),

            // Never null: generation requires a bounded range, and the window is
            // kept wide enough to clear the longest interval pattern (30 days).
            'end_date' => (clone $startDate)->modify('+120 days')->format('Y-m-d'),
            'total_spots' => $this->faker->randomElement([6, 8, 10, 12]),
            'status' => ClassStatusEnum::ACTIVE->value,
        ];
    }

    /**
     * Schedule by a fixed interval instead of weekdays.
     */
    public function interval(?string $patternName = null): static
    {
        return $this->state(function () use ($patternName) {
            $pattern = $patternName
                ? RecurrencePattern::where('name', $patternName)->first()
                : RecurrencePattern::inRandomOrder()->first();

            $pattern ??= RecurrencePattern::factory()->create();

            return [
                'recurrence_pattern_id' => $pattern->id,
                'weekdays' => null,
            ];
        });
    }

    /**
     * @param  list<WeekdayEnum|string>  $weekdays
     */
    public function onWeekdays(array $weekdays): static
    {
        return $this->state(fn () => [
            'weekdays' => array_map(
                fn ($day) => $day instanceof WeekdayEnum ? $day->value : (string) $day,
                $weekdays
            ),
            'recurrence_pattern_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassStatusEnum::INACTIVE->value,
        ]);
    }
}
