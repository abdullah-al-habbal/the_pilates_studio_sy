<?php

namespace Database\Factories;

use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id'   => Classes::inRandomOrder()->first()?->id ?? Classes::factory(),
            'url'        => 'class-images/' . $this->faker->uuid() . '.jpg',
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn() => ['is_primary' => true]);
    }
}
