<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class AppSettingSeeder extends Seeder
{
    use CopiesSourceImage;

    public function run(): void
    {
        $settings = Config::get('app_settings.defaults', []);
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');

        foreach ($settings as $setting) {
            if (($setting['type'] ?? null) === 'image') {
                $existing = AppSetting::where('key', $setting['key'])->first();

                if (! $existing || empty($existing->value)) {
                    $identifier = str_replace('_', '-', $setting['key']);
                    $setting['value'] = $this->copySourceImage($sourcePath, 'app-settings', $identifier, $setting['key']);
                } else {
                    $setting['value'] = $existing->value;
                }
            }

            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
