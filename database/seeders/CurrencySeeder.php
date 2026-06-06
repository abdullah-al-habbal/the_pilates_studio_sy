<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'decimal_places' => 2,
                'exchange_rate' => 1.0,
                'is_active' => true,
            ],
            [
                'code' => 'SYP',
                'name' => ['en' => 'Syrian Pound', 'ar' => 'ليرة سورية'],
                'symbol' => '£S',
                'decimal_places' => 0,
                'exchange_rate' => 13000.0,
                'is_active' => true,
            ]
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
