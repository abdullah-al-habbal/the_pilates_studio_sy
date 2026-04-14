<?php

// database/seeders/CenterMerchandiseCategorySeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CenterMerchandiseCategory;
use Illuminate\Database\Seeder;

class CenterMerchandiseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['en' => 'Apparel', 'ar' => 'الملابس'],
            ['en' => 'Equipment', 'ar' => 'المعدات'],
            ['en' => 'Accessories', 'ar' => 'الإكسسوارات'],
            ['en' => 'Nutrition', 'ar' => 'التغذية'],
            ['en' => 'Recovery', 'ar' => 'الاسترداد'],
        ];

        foreach ($categories as $name) {
            CenterMerchandiseCategory::firstOrCreate(
                ['name->en' => $name['en']],
                ['name' => $name]
            );
        }
    }
}
