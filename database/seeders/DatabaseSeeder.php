<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->cleanupGeneratedAssets();

        $this->call([
            CurrencySeeder::class,
            LanguageSeeder::class,
            RecurrencePatternSeeder::class,
            ClassCategorySeeder::class,
            InstructorSeeder::class,
            PackageSeeder::class,
            StaticPageSeeder::class,
            CenterMerchandiseCategorySeeder::class,
            CenterMerchandiseSeeder::class,
            UserSeeder::class,
            UserSettingSeeder::class,
            ClassesSeeder::class,
            ClassImageSeeder::class,
            ClassSessionSeeder::class,
            BookingSeeder::class,
            BookingSessionSeeder::class,
            AppNotificationSeeder::class,
            NotificationTemplateSeeder::class,
            AppSettingSeeder::class,
            MobileAppVersionSeeder::class,
            TestimonialSeeder::class,
        ]);
    }

    private function cleanupGeneratedAssets(): void
    {
        $directory = storage_path('app/public/data-images');

        if (File::exists($directory)) {
            File::deleteDirectory($directory);
        }
    }
}
