<?php

namespace Database\Seeders;

use App\Models\ClassCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClassCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['en' => 'Reformer', 'ar' => 'ريفورمر', 'color' => '#A07850'],
            ['en' => 'Mat',      'ar' => 'مات',      'color' => '#6B8E6B'],
            ['en' => 'Tower',    'ar' => 'تاور',      'color' => '#5B7FA6'],
        ];

        foreach ($categories as $cat) {
            ClassCategory::firstOrCreate(
                ['slug' => Str::slug($cat['en'])],
                [
                    'name'  => ['en' => $cat['en'], 'ar' => $cat['ar']],
                    'color' => $cat['color'],
                ]
            );
        }
    }
}
