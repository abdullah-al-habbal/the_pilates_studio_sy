<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClassCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Reformer',
            'Mat',
            'Tower',
            'Barre',
            'Pilates Chair',
        ]);

        return [
            'name'  => ['en' => $name, 'ar' => $name],
            'slug'  => Str::slug($name),
            'color' => $this->faker->hexColor(),
        ];
    }
}
