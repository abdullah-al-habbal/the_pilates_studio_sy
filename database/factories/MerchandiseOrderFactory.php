<?php

namespace Database\Factories;

use App\Models\CenterMerchandise;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchandiseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchandise_id' => CenterMerchandise::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'customer_id' => User::factory(),
            'created_by' => null,
            'ordered_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'currency_id' => Currency::factory()->active(),
            'paid_amount' => $this->faker->numberBetween(1000, 50000),
        ];
    }
}
