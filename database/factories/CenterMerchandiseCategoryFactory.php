<?php

// database/factories/CenterMerchandiseCategoryFactory.php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CenterMerchandiseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterMerchandiseCategoryFactory extends Factory
{
    protected $model = CenterMerchandiseCategory::class;

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
