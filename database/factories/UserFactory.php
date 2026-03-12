<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fullname'          => $this->faker->name(),
            'phone_number'      => '+971' . $this->faker->unique()->numerify('#########'),
            'email'             => $this->faker->unique()->safeEmail(),
            'password'          => Hash::make('password'),
            'date_of_birth'     => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'email_verified_at' => now(),
            'otp_code'          => null,
            'otp_expires_at'    => null,
            'deactivated_at'    => null,
            'deleted_by'        => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn() => ['email_verified_at' => null]);
    }

    public function deactivated(): static
    {
        return $this->state(fn() => ['deactivated_at' => now()]);
    }
}
