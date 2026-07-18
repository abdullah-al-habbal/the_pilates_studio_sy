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
        $directories = ['class-images', 'instructors', 'static-pages', 'testimonials'];

        foreach ($directories as $dir) {
            $path = storage_path("app/public/{$dir}");

            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }
    }
}
