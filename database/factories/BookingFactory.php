<?php

namespace Database\Factories;

use App\Enums\BookingStatusEnum;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $package   = Package::inRandomOrder()->first() ?? Package::factory()->create();
        $total     = $package->total_credits;
        $remaining = $this->faker->numberBetween(1, $total);

        return [
            'user_id'           => User::inRandomOrder()->first()?->id ?? User::factory(),
            'created_by'        => null,
            'package_id'        => $package->id,
            'total_credits'     => $total,
            'remaining_credits' => $remaining,
            'status'            => BookingStatusEnum::ACTIVE->value,
            'expires_at'        => $this->faker->optional(0.4)
                ->dateTimeBetween('+1 month', '+1 year'),
            'paid_amount'       => $this->faker->numberBetween(10000, 100000),
            'currency_id'       => Currency::where('code', 'SYP')->first()?->id,
        ];
    }

    public function exhausted(): static
    {
        return $this->state(fn() => [
            'remaining_credits' => 0,
            'status'            => BookingStatusEnum::EXHAUSTED->value,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status'     => BookingStatusEnum::EXPIRED->value,
            'expires_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::CANCELLED->value,
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn() => [
            'status' => BookingStatusEnum::FROZEN->value,
            'frozen_at' => now(),
        ]);
    }
}
