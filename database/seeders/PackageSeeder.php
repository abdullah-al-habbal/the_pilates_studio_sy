<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Services\Currency\PricingService;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $pricing = app(PricingService::class);
        $baseCurrencyId = $pricing->getBaseCurrencyId();

        $packages = [
            ['name' => ['en' => '4 Sessions Pack', 'ar' => 'باقة 4 حصص'], 'total_credits' => 4, 'price' => 20000, 'validity_days' => 30, 'is_active' => true],
            ['name' => ['en' => '8 Sessions Pack', 'ar' => 'باقة 8 حصص'], 'total_credits' => 8, 'price' => 38000, 'validity_days' => 60, 'is_active' => true],
            ['name' => ['en' => '12 Sessions Pack', 'ar' => 'باقة 12 حصة'], 'total_credits' => 12, 'price' => 54000, 'validity_days' => 90, 'is_active' => true],
            ['name' => ['en' => '20 Sessions Pack', 'ar' => 'باقة 20 حصة'], 'total_credits' => 20, 'price' => 84000, 'validity_days' => 180, 'is_active' => true],
        ];

        foreach ($packages as $packageData) {
            $price = $packageData['price'];
            unset($packageData['price']);

            $package = Package::firstOrCreate(
                ['name->en' => $packageData['name']['en']],
                $packageData
            );

            $package->prices()->updateOrCreate(
                ['currency_id' => $baseCurrencyId],
                ['amount' => $price]
            );

            $package->prices()
                ->where('currency_id', '!=', $baseCurrencyId)
                ->delete();
        }
    }
}
