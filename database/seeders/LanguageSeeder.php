<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'direction' => 'ltr', 'is_active' => true,  'is_default' => true],
            ['code' => 'ar', 'name' => 'Arabic',  'direction' => 'rtl', 'is_active' => true,  'is_default' => false],
        ];

        foreach ($languages as $language) {
            Language::firstOrCreate(['code' => $language['code']], $language);
        }
    }
}
