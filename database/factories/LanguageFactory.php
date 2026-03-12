<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'       => $this->faker->unique()->languageCode(),
            'name'       => $this->faker->languageCode(),
            'direction'  => 'ltr',
            'is_active'  => true,
            'is_default' => false,
        ];
    }
}
