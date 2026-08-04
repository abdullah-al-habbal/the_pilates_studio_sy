<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(5)->create();
        User::factory(3)->deactivated()->create();
    }
}
