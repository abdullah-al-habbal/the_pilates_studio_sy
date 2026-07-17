<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class StaticPageSeeder extends Seeder
{
    use CopiesSourceImage;

    public function run(): void
    {
        $pages = Config::get('static_pages.defaults', []);
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');

        foreach ($pages as $page) {
            $page['image'] = $this->copySourceImage($sourcePath, 'static-pages', $page['slug'], 'page');

            StaticPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}