<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use App\Services\Finance\DailyBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class DailyBalanceServiceRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_summary_for_range_aggregates_across_multiple_days(): void
    {
        $currency = Currency::factory()->create([
            'code' => 'USD',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'exchange_rate' => 1,
        ]);
        $package = Package::factory()->create();
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        Booking::factory()->create([
            'user_id' => $userOne->id,
            'package_id' => $package->id,
            'currency_id' => $currency->id,
            'paid_amount' => 10000,
            'created_at' => Carbon::parse('2026-05-01 10:00:00'),
        ]);

        Booking::factory()->create([
            'user_id' => $userTwo->id,
            'package_id' => $package->id,
            'currency_id' => $currency->id,
            'paid_amount' => 5000,
            'created_at' => Carbon::parse('2026-05-15 10:00:00'),
        ]);

        $service = app(DailyBalanceService::class);
        $summary = $service->getSummaryForRange(
            Carbon::parse('2026-05-01')->startOfDay(),
            Carbon::parse('2026-05-31')->endOfDay(),
            ['USD'],
        );

        $usd = $summary->firstWhere('currencyCode', 'USD');
        $this->assertNotNull($usd);
        $this->assertSame(15000, $usd->packageRevenue);
        $this->assertSame(15000, $usd->totalRevenue);
    }
}
