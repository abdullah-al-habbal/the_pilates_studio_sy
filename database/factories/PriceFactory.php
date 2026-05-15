<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Services\Currency\PricingService;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{

    public function definition(): array
    {
        $pricing = app(PricingService::class);
        $baseCurrencyId = $pricing->getBaseCurrencyId();

        return [
            'currency_id' => $baseCurrencyId,
            'amount' => fake()->numberBetween(1000, 100000),
        ];
    }

    public function usd(): static
    {
        $pricing = app(PricingService::class);
        $baseCurrencyId = $pricing->getBaseCurrencyId();

        return $this->state(fn(array $attributes) => [
            'currency_id' => $baseCurrencyId,
        ]);
    }

    public function forCurrency(int $currencyId): static
    {
        return $this->state(fn(array $attributes) => [
            'currency_id' => $currencyId,
        ]);
    }
}
