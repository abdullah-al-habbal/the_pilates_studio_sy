<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CenterMerchandise;
use App\Models\CenterMerchandiseCategory;
use App\Services\Currency\PricingService;
use Illuminate\Database\Seeder;

class CenterMerchandiseSeeder extends Seeder
{
    public function run(): void
    {
        $pricing = app(PricingService::class);
        $baseCurrencyId = $pricing->getBaseCurrencyId();

        $categories = CenterMerchandiseCategory::all();
        if ($categories->isEmpty()) {
            return;
        }

        $items = [
            [
                'name' => ['en' => 'Yoga Mat', 'ar' => 'حصيرة يوجا'],
                'description' => ['en' => 'High quality non-slip yoga mat.', 'ar' => 'حصيرة يوجا عالية الجودة غير قابلة للانزلاق.'],
                'base_price' => 325000,
                'stock_quantity' => 10,
                'category_id' => $categories->random()->id,
            ],
            [
                'name' => ['en' => 'Water Bottle', 'ar' => 'قنينة ماء'],
                'description' => ['en' => '1L stainless steel water bottle.', 'ar' => 'قنينة ماء سعة 1 لتر من الفولاذ المقاوم للصدأ.'],
                'base_price' => 195000,
                'stock_quantity' => 20,
                'category_id' => $categories->random()->id,
            ],
        ];

        foreach ($items as $itemData) {
            $basePrice = $itemData['base_price'];
            unset($itemData['base_price']);

            $merchandise = CenterMerchandise::create($itemData);

            $merchandise->prices()->create([
                'currency_id' => $baseCurrencyId,
                'amount' => $basePrice,
            ]);

            $merchandise->prices()
                ->where('currency_id', '!=', $baseCurrencyId)
                ->delete();
        }
    }
}
