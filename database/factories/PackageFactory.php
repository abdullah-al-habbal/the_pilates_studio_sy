<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    public function definition(): array
    {
        $credits = $this->faker->randomElement([4, 8, 12, 20]);
        $name = "{$credits} Sessions Pack";

        return [
            'name' => ['en' => $name, 'ar' => "باقة {$credits} جلسات"],
            'total_credits' => $credits,
            'is_active' => true,
            'validity_days' => $this->faker->numberBetween(15, 365),
            'type' => 'standard',
            'generated_reason' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Package $package) {
            $sypCurrency = Currency::factory()->create([
                'code' => 'SYP',
                'name' => ['en' => 'Syrian Pound', 'ar' => 'ليرة سورية'],
                'symbol' => '£S',
                'decimal_places' => 0,
                'exchange_rate' => 1,
                'is_active' => true,
            ]);

            $package->prices()->create([
                'currency_id' => $sypCurrency->id,
                'amount' => $package->total_credits * 5000,
            ]);

            $usdCurrency = Currency::factory()->create([
                'code' => 'USD',
                'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'decimal_places' => 2,
                'exchange_rate' => 13000,
                'is_active' => true,
            ]);

            $package->prices()->create([
                'currency_id' => $usdCurrency->id,
                'amount' => (int) round(($package->total_credits * 5000) / 13000),
            ]);
        });
    }

    public function withPrice(int $amount, ?int $currencyId = null): static
    {
        return $this->afterCreating(function (Package $package) use ($amount, $currencyId) {
            $currencyId = $currencyId ?? Currency::where('code', 'SYP')->first()?->id;

            if ($currencyId) {
                $package->prices()->create([
                    'currency_id' => $currencyId,
                    'amount' => $amount,
                ]);
            }
        });
    }
}