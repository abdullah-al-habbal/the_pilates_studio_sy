<?php

namespace Database\Factories;

use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StaticPageFactory extends Factory
{
    use CopiesSourceImage;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);
        $slug = Str::slug($title);

        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');
        $imagePath = $this->copySourceImage($sourcePath, 'static-pages', $slug);

        return [
            'slug' => $slug,
            'title' => ['en' => ucwords($title), 'ar' => ucwords($title)],
            'image' => $imagePath,
            'content' => ['en' => '<p>'.$this->faker->paragraph().'</p>', 'ar' => ''],
        ];
    }
}
