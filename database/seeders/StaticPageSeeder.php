<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = Config::get('static_pages.defaults', []);

        foreach ($pages as $page) {
            StaticPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}