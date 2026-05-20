<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        $code = $this->faker->unique()->currencyCode();

        return [
            'code' => $code,
            'name' => ['en' => $code, 'ar' => $code],
            'symbol' => '$',
            'decimal_places' => 2,
            'exchange_rate' => 1.0,
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => ['is_active' => true]);
    }
}
