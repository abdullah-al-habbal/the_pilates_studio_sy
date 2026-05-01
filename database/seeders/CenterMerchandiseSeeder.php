<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CenterMerchandise;
use App\Models\CenterMerchandiseCategory;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CenterMerchandiseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = CenterMerchandiseCategory::all();
        if ($categories->isEmpty()) {
            return;
        }

        $items = [
            [
                'name' => ['en' => 'Yoga Mat', 'ar' => 'سجادة يوغا'],
                'description' => ['en' => 'High quality non-slip yoga mat.', 'ar' => 'سجادة يوغا عالية الجودة مانعة للانزلاق.'],
                'prices' => [
                    'USD' => 2500,
                    'SYP' => 325000,
                ],
                'stock_quantity' => 10,
                'category_id' => $categories->random()->id,
            ],
            [
                'name' => ['en' => 'Water Bottle', 'ar' => 'زجاجة ماء'],
                'description' => ['en' => '1L stainless steel water bottle.', 'ar' => 'زجاجة ماء سعة 1 لتر من الفولاذ المقاوم للصدأ.'],
                'prices' => [
                    'USD' => 1500,
                    'SYP' => 195000,
                ],
                'stock_quantity' => 20,
                'category_id' => $categories->random()->id,
            ],
        ];

        foreach ($items as $itemData) {
            $prices = $itemData['prices'];
            unset($itemData['prices']);

            $merchandise = CenterMerchandise::create($itemData);

            foreach ($prices as $currencyCode => $amount) {
                $currency = Currency::where('code', $currencyCode)->first();
                if ($currency) {
                    $merchandise->prices()->create([
                        'currency_id' => $currency->id,
                        'amount' => $amount,
                    ]);
                }
            }
        }
    }
}
