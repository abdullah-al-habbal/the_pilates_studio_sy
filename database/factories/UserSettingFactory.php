<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'preferred_language_id' => Language::where('is_active', true)->inRandomOrder()->first()?->id,
            'allow_notifications'   => $this->faker->boolean(80),
            'fcm_token'             => null,
        ];
    }
}
