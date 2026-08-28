<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingSourceTypeEnum;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Services\Finance\DailyBalanceService;
use App\Services\Finance\ExchangeRateSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `purchased_at` as the canonical business date.
 *
 * See docs/historical-backfill/decisions/D-A13-global-purchased-at-adoption.md
 */
final class BookingPurchasedAtTest extends TestCase
{
    use RefreshDatabase;

    private BookingEloquentRepository $repository;

    private Package $package;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(BookingEloquentRepository::class);
        $this->package = Package::factory()->create(['total_credits' => 8]);
        $this->currency = Currency::query()->firstOrFail();
    }

    private function booking(array $attributes = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'package_id' => $this->package->id,
            'currency_id' => $this->currency->id,
            'paid_amount' => 50_000,
        ], $attributes));
    }

    // ---------------------------------------------------------------- schema

    #[Test]
    public function purchased_at_is_not_nullable_and_defaults_to_now(): void
    {
        $column = collect(DB::select('SHOW COLUMNS FROM bookings'))
            ->firstWhere('Field', 'purchased_at');

        $this->assertNotNull($column, 'purchased_at column is missing.');
        $this->assertSame('NO', $column->Null, 'purchased_at must be NOT NULL.');
        $this->assertStringContainsStringIgnoringCase(
            'current_timestamp',
            (string) $column->Default,
            'purchased_at must default to CURRENT_TIMESTAMP, mirroring merchandise_orders.ordered_at.'
        );
    }

    #[Test]
    public function a_write_that_bypasses_eloquent_still_gets_a_business_date(): void
    {
        // The hardening rationale: `where('purchased_at', ...)` never matches NULL, so a raw
        // insert without the column must not be able to vanish from every financial report.
        $userId = User::factory()->create()->id;

        DB::table('bookings')->insert([
            'user_id' => $userId,
            'package_id' => $this->package->id,
            'total_credits' => 8,
            'remaining_credits' => 8,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchasedAt = DB::table('bookings')->where('user_id', $userId)->value('purchased_at');

        $this->assertNotNull($purchasedAt);
    }

    // ------------------------------------------------------------ model hook

    #[Test]
    public function the_creating_hook_populates_purchased_at_when_omitted(): void
    {
        $booking = $this->booking();

        $this->assertNotNull($booking->purchased_at);
        $this->assertTrue(
            $booking->purchased_at->diffInSeconds(now(), true) < 5,
            'An omitted purchased_at should default to the moment of sale.'
        );
    }

    #[Test]
    public function an_explicit_purchased_at_survives_the_creating_hook(): void
    {
        $historical = now()->subMonths(2)->startOfDay();

        $booking = $this->booking([
            'purchased_at' => $historical,
            'source_type' => BookingSourceTypeEnum::HISTORICAL_BACKFILL->value,
        ]);

        $this->assertTrue($booking->fresh()->purchased_at->equalTo($historical));
    }

    #[Test]
    public function created_at_remains_the_entry_timestamp_while_purchased_at_holds_the_business_date(): void
    {
        $historical = now()->subMonths(2)->startOfDay();

        $booking = $this->booking([
            'purchased_at' => $historical,
            'source_type' => BookingSourceTypeEnum::HISTORICAL_BACKFILL->value,
        ])->fresh();

        $this->assertTrue($booking->purchased_at->equalTo($historical), 'purchased_at must be the paper date.');
        $this->assertTrue(
            $booking->created_at->diffInSeconds(now(), true) < 5,
            'created_at must stay the data-entry timestamp.'
        );
        $this->assertFalse($booking->created_at->equalTo($booking->purchased_at));
    }

    // ------------------------------------------------------------ reporting

    #[Test]
    public function it_reports_revenue_by_purchased_at_not_created_at(): void
    {
        $historical = now()->subMonths(2)->startOfDay();

        $this->booking([
            'purchased_at' => $historical,
            'paid_amount' => 70_000,
            'source_type' => BookingSourceTypeEnum::HISTORICAL_BACKFILL->value,
        ]);

        $today = $this->repository
            ->getRevenueByCurrency(now()->startOfDay(), now()->endOfDay())
            ->firstWhere('currency_id', $this->currency->id);

        $this->assertNull($today, 'A backfill entered today must not land in today\'s revenue.');

        $itsOwnMonth = $this->repository
            ->getRevenueByCurrency($historical->copy()->startOfMonth(), $historical->copy()->endOfMonth())
            ->firstWhere('currency_id', $this->currency->id);

        $this->assertSame(70_000, $itsOwnMonth->total_revenue);
    }

    #[Test]
    public function a_same_day_paper_sale_appears_in_todays_balance(): void
    {
        // Regression sentinel for D-A13 §C3: reintroducing a source_type exclusion on the
        // revenue queries would wrongly hide a package that was genuinely bought today.
        $this->booking([
            'purchased_at' => now(),
            'paid_amount' => 25_000,
            'source_type' => BookingSourceTypeEnum::HISTORICAL_BACKFILL->value,
        ]);

        $summary = app(DailyBalanceService::class)
            ->getSummary(now()->toDateString())
            ->firstWhere('currencyId', $this->currency->id);

        $this->assertNotNull($summary);
        $this->assertSame(25_000, $summary->packageRevenue);
    }

    #[Test]
    public function total_count_and_total_revenue_by_currency_also_key_on_purchased_at(): void
    {
        $historical = now()->subMonths(2)->startOfDay();

        $this->booking(['purchased_at' => $historical, 'paid_amount' => 40_000]);

        $this->assertSame(0, $this->repository->getTotalCount(now()->startOfDay(), now()->endOfDay()));
        $this->assertSame(
            0,
            $this->repository->getTotalRevenueByCurrency(
                $this->currency->id,
                now()->startOfDay(),
                now()->endOfDay()
            ),
            'getTotalRevenueByCurrency was the site missed in the original file list (D-A13 §C1).'
        );

        $this->assertSame(
            40_000,
            $this->repository->getTotalRevenueByCurrency(
                $this->currency->id,
                $historical->copy()->startOfMonth(),
                $historical->copy()->endOfMonth()
            )
        );
    }

    #[Test]
    public function standard_bookings_set_purchased_at_to_the_moment_of_sale(): void
    {
        $booking = $this->booking(['source_type' => BookingSourceTypeEnum::STANDARD->value])->fresh();

        $this->assertTrue(
            $booking->purchased_at->diffInSeconds($booking->created_at, true) < 5,
            'For a live sale the business date and the audit date should coincide.'
        );
    }

    // ------------------------------------------------------- historical rate

    #[Test]
    public function get_historical_rate_ignores_backfilled_bookings(): void
    {
        // Regression sentinel for D-A13 §C2. A backfill carries today's rate by default while
        // its purchased_at sits months back; counting it would report today's rate as that
        // period's historical rate.
        $foreign = Currency::query()
            ->where('id', '!=', app(ExchangeRateSnapshotService::class)->currencyService->getBaseCurrency()->id)
            ->first();

        if ($foreign === null) {
            $this->markTestSkipped('No non-base currency configured.');
        }

        $asOf = now()->subMonths(2);

        $this->booking([
            'currency_id' => $foreign->id,
            'purchased_at' => $asOf->copy()->subDay(),
            'exchange_rate_snapshot' => 99999.0,
            'source_type' => BookingSourceTypeEnum::HISTORICAL_BACKFILL->value,
        ]);

        $rate = app(ExchangeRateSnapshotService::class)->getHistoricalRate($foreign->id, $asOf);

        $this->assertNotSame(99999.0, $rate, 'A backfilled snapshot must not seed the historical rate.');
    }

    #[Test]
    public function get_historical_rate_uses_purchased_at_for_normal_bookings(): void
    {
        $foreign = Currency::query()
            ->where('id', '!=', app(ExchangeRateSnapshotService::class)->currencyService->getBaseCurrency()->id)
            ->first();

        if ($foreign === null) {
            $this->markTestSkipped('No non-base currency configured.');
        }

        $this->booking([
            'currency_id' => $foreign->id,
            'purchased_at' => now()->subMonths(2)->subDay(),
            'exchange_rate_snapshot' => 4242.0,
        ]);

        $rate = app(ExchangeRateSnapshotService::class)
            ->getHistoricalRate($foreign->id, now()->subMonths(2));

        $this->assertSame(4242.0, $rate);
    }
}
