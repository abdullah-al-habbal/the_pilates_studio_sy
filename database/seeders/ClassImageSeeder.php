<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\ClassImage;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Seeder;

class ClassImageSeeder extends Seeder
{
    use CopiesSourceImage;

    public function run(): void
    {
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');

        Classes::all()->each(function (Classes $class) use ($sourcePath) {
            if ($class->primaryImage()->exists()) {
                return;
            }

            $primaryUrl = $this->copySourceImage($sourcePath, 'class-images', (string) $class->id, 'primary');

            ClassImage::create([
                'class_id' => $class->id,
                'url' => $primaryUrl,
                'is_primary' => true,
            ]);

            $extras = random_int(1, 2);
            for ($i = 1; $i <= $extras; $i++) {
                $extraUrl = $this->copySourceImage($sourcePath, 'class-images', (string) $class->id, "gallery-{$i}");

                ClassImage::create([
                    'class_id' => $class->id,
                    'url' => $extraUrl,
                    'is_primary' => false,
                ]);
            }
        });
    }
}
