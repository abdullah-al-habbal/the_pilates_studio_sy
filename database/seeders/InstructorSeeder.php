<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class InstructorSeeder extends Seeder
{
    use CopiesSourceImage;

    public function run(): void
    {
        $instructors = Config::get('instructors.defaults', []);
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');

        foreach ($instructors as $data) {
            $slug = Str::slug($data['name']['en']);

            $data['image'] = $this->copySourceImage($sourcePath, 'instructors', $slug, 'profile');

            Instructor::firstOrCreate(
                ['name->en' => $data['name']['en']],
                $data
            );
        }
    }
}
