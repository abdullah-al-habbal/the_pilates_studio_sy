<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StaticPageFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'slug'    => Str::slug($title),
            'title'   => ['en' => ucwords($title), 'ar' => ucwords($title)],
            'image'   => null,
            'content' => ['en' => '<p>' . $this->faker->paragraph() . '</p>', 'ar' => ''],
        ];
    }
}
