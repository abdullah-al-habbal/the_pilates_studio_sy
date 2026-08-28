<?php

// database/factories/CenterMerchandiseCategoryFactory.php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CenterMerchandiseCategory;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

#[UseModel(CenterMerchandiseCategory::class)]
class CenterMerchandiseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->unique()->words(2, true),
                'ar' => $this->faker->unique()->words(2, true),
            ],
        ];
    }
}
