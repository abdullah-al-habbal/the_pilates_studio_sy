<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    public function definition(): array
    {
        $credits = $this->faker->randomElement([4, 8, 12, 20]);
        $name    = "{$credits} Sessions Pack";

        return [
            'name'          => ['en' => $name, 'ar' => "باقة {$credits} جلسات"],
            'total_credits' => $credits,
            'price'         => $credits * 5000,
            'is_active'     => true,
        ];
    }
}
