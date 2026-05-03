<?php

namespace Database\Seeders;

use App\Models\ClubExpense;
use Illuminate\Database\Seeder;

class ClubExpenseSeeder extends Seeder
{
    public function run(): void
    {
        ClubExpense::factory(30)->create();
    }
}
