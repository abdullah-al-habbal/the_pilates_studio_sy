<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class TestimonialSeeder extends Seeder
{
    use CopiesSourceImage;

    public function run(): void
    {
        $testimonials = Config::get('testimonials.defaults', []);
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');

        foreach ($testimonials as $data) {
            $slug = Str::slug($data['name']['en']);

            $data['avatar'] = $this->copySourceImage($sourcePath, 'testimonials', $slug, 'avatar');

            Testimonial::firstOrCreate(
                ['name->en' => $data['name']['en']],
                $data
            );
        }
    }
}
