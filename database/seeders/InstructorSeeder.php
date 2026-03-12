<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        $fixed = [
            ['en' => 'Sarah Jrame', 'ar' => 'سارة جريم'],
            ['en' => 'Adam Kim', 'ar' => 'آدم كيم'],
            ['en' => 'Emma Wall', 'ar' => 'إيما وول'],
        ];

        foreach ($fixed as $name) {
            Instructor::firstOrCreate(
                ['name->en' => $name['en']],
                ['name' => $name]
            );
        }

        Instructor::factory(4)->create();
    }
}
