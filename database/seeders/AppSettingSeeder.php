<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = Config::get('app_settings.defaults', []);

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}