<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@studio.com'],
            [
                'fullname' => 'Studio Admin',
                'phone_number' => '+97100000002',
                'password' => Hash::make('password'),
                'date_of_birth' => '1990-01-01',
                'email_verified_at' => now(),
                'role' => UserRoleEnum::MAIN_ADMIN->value,
            ]
        );

        User::firstOrCreate(
            ['email' => 'adam.kim@gmail.com'],
            [
                'fullname' => 'Adam Kim',
                'phone_number' => '+97100000001',
                'password' => Hash::make('password'),
                'date_of_birth' => '1990-01-01',
                'email_verified_at' => now(),
                'role' => UserRoleEnum::ADMIN->value,
            ]
        );
    }
}
