<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            RecurrencePatternSeeder::class,
            ClassCategorySeeder::class,
            InstructorSeeder::class,
            PackageSeeder::class,
            StaticPageSeeder::class,
            UserSeeder::class,
            UserSettingSeeder::class,
            ClassesSeeder::class,
            ClassImageSeeder::class,
            ClassSessionSeeder::class,
            BookingSeeder::class,
            BookingSessionSeeder::class,
            AppNotificationSeeder::class,
            AppSettingSeeder::class,
            MobileAppVersionSeeder::class,
        ]);
    }
}
