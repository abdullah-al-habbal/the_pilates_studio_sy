<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CenterMerchandise;
use App\Models\CenterMerchandiseCategory;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(CenterMerchandise::class)]
class CenterMerchandiseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->unique()->words(2, true),
                'ar' => $this->faker->unique()->words(2, true),
            ],
            'description' => [
                'en' => $this->faker->sentence(),
                'ar' => $this->faker->sentence(),
            ],
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'category_id' => CenterMerchandiseCategory::factory(),
        ];
    }
}
