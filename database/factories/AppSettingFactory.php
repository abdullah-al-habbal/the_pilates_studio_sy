<?php

namespace Database\Factories;

use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(AppSetting::class)]
class AppSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
            'description' => $this->faker->sentence(),
        ];
    }
}
