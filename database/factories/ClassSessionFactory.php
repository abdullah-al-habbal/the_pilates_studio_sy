<?php

namespace Database\Factories;

use App\Enums\ClassSessionStatusEnum;
use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassSessionFactory extends Factory
{
    public function definition(): array
    {
        $startHour = $this->faker->randomElement([7, 8, 9, 10, 17, 18]);
        $startTime = sprintf('%02d:00:00', $startHour);
        $endTime   = sprintf('%02d:50:00', $startHour);

        return [
            'class_id'    => Classes::inRandomOrder()->first()?->id ?? Classes::factory(),
            'date'        => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'total_spots' => $this->faker->randomElement([6, 8, 10]),
            'status'      => ClassSessionStatusEnum::SCHEDULED->value,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'date'   => $this->faker->dateTimeBetween('-60 days', '-1 day')->format('Y-m-d'),
            'status' => ClassSessionStatusEnum::COMPLETED->value,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ClassSessionStatusEnum::CANCELLED->value,
        ]);
    }

    public function forClass(Classes $class): static
    {
        return $this->state(fn(array $attributes) => [
            'class_id'    => $class->id,
            'start_time'  => $class->start_time,
            'end_time'    => $class->end_time,
            'total_spots' => $class->total_spots,
        ]);
    }
}
