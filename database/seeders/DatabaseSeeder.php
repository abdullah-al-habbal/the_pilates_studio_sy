<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('production')) {
            $this->cleanupGeneratedAssets();
        }

        $seeders = [
            CurrencySeeder::class,
            LanguageSeeder::class,
            RecurrencePatternSeeder::class,
            ClassCategorySeeder::class,
            InstructorSeeder::class,
            PackageSeeder::class,
            StaticPageSeeder::class,
            CenterMerchandiseCategorySeeder::class,
            CenterMerchandiseSeeder::class,
            AdminSeeder::class,
            UserSettingSeeder::class,
            AppNotificationSeeder::class,
            NotificationTemplateSeeder::class,
            AppSettingSeeder::class,
            MobileAppVersionSeeder::class,
            TestimonialSeeder::class,
        ];

        if (! app()->environment('production')) {
            array_splice($seeders, 10, 0, [
                ClientSeeder::class,
                ClassesSeeder::class,
                ClassImageSeeder::class,
                ClassSessionSeeder::class,
                BookingSeeder::class,
                BookingSessionSeeder::class,
            ]);
        }

        $this->call($seeders);
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
