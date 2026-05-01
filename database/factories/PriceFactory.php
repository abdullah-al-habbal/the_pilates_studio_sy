<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{

    public function definition(): array
    {
        return [
            'currency_id' => Currency::factory(),
            'amount' => fake()->numberBetween(1000, 100000),
        ];
    }

    public function usd(): static
    {
        return $this->state(fn(array $attributes) => [
            'currency_id' => Currency::where('code', 'USD')->first()?->id ?? Currency::factory(),
        ]);
    }

    public function syp(): static
    {
        return $this->state(fn(array $attributes) => [
            'currency_id' => Currency::where('code', 'SYP')->first()?->id ?? Currency::factory(),
        ]);
    }
}
