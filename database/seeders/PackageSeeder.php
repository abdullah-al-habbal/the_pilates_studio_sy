<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => ['en' => '4 Sessions Pack',  'ar' => 'باقة 4 جلسات'],  'total_credits' => 4,  'price' => 20000, 'is_active' => true],
            ['name' => ['en' => '8 Sessions Pack',  'ar' => 'باقة 8 جلسات'],  'total_credits' => 8,  'price' => 38000, 'is_active' => true],
            ['name' => ['en' => '12 Sessions Pack', 'ar' => 'باقة 12 جلسة'],  'total_credits' => 12, 'price' => 54000, 'is_active' => true],
            ['name' => ['en' => '20 Sessions Pack', 'ar' => 'باقة 20 جلسة'],  'total_credits' => 20, 'price' => 84000, 'is_active' => true],
        ];

        foreach ($packages as $package) {
            Package::firstOrCreate(
                ['name->en' => $package['name']['en']],
                $package
            );
        }
    }
}
