<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppNotificationFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->randomElement([
            'Class tomorrow',
            'Booking confirmed',
            'Session cancelled',
            'Credits running low',
        ]);

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'title'   => ['en' => $title,                    'ar' => $title],
            'message' => ['en' => $this->faker->sentence(),  'ar' => ''],
            'read_at' => $this->faker->optional(0.5)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn() => ['read_at' => null]);
    }

    public function read(): static
    {
        return $this->state(fn() => ['read_at' => now()]);
    }
}
