<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // fix: we must make those a config from .env
        User::firstOrCreate(
            ['email' => 'adam.kim@gmail.com'],
            [
                'fullname' => 'Adam Kim',
                'phone_number' => '+97100000001',
                'password' => Hash::make('password'),
                'date_of_birth' => '1990-01-01',
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'admin@studio.com'],
            [
                'fullname' => 'Studio Admin',
                'phone_number' => '+97100000002',
                'password' => Hash::make('password'),
                'date_of_birth' => '1990-01-01',
                'email_verified_at' => now(),
            ]
        );

        User::factory(5)->create();
        User::factory(3)->deactivated()->create();
    }
}
