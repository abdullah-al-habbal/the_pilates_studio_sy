<?php

namespace Database\Factories;

use App\Models\Classes;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassImageFactory extends Factory
{
    use CopiesSourceImage;

    public function definition(): array
    {
        $class = Classes::inRandomOrder()->first() ?? Classes::factory()->create();
        $classId = $class->id;

        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');
        $relativePath = $this->copySourceImage($sourcePath, 'class-images', (string) $classId);

        return [
            'class_id' => $classId,
            'url' => $relativePath,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
