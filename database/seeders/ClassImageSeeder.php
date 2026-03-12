<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\ClassImage;
use Illuminate\Database\Seeder;

class ClassImageSeeder extends Seeder
{
    public function run(): void
    {
        Classes::all()->each(function (Classes $class) {
            if ($class->primaryImage()->exists()) {
                return;
            }

            ClassImage::create([
                'class_id'   => $class->id,
                'url'        => 'class-images/placeholder-' . $class->id . '.jpg',
                'is_primary' => true,
            ]);

            $extras = rand(1, 2);
            for ($i = 1; $i <= $extras; $i++) {
                ClassImage::create([
                    'class_id'   => $class->id,
                    'url'        => 'class-images/gallery-' . $class->id . '-' . $i . '.jpg',
                    'is_primary' => false,
                ]);
            }
        });
    }
}
