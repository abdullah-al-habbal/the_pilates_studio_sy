<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingSourceTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Currency;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\User;
use App\Services\Booking\HistoricalBackfillService;
use App\Services\Currency\PricingService;
use App\Services\Finance\DailyBalanceService;
use App\Services\Validation\HistoricalBackfillValidatorService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 2 — historical backfill domain core.
 *
 * @see docs/historical-backfill/plan/phase-2-domain-core.md
 */
final class HistoricalBackfillTest extends TestCase
{
    use RefreshDatabase;

    private HistoricalBackfillValidatorService $validator;

    private HistoricalBackfillService $service;

    private User $client;

    private User $admin;

    private Currency $currency;

    private Package $package;

    private ?Classes $class = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = app(HistoricalBackfillValidatorService::class);
        $this->service = app(HistoricalBackfillService::class);

        $this->client = User::factory()->create();
        $this->admin = User::factory()->create();

        // Must be the configured base currency (USD) and active, or CurrencyService throws.
        $this->currency = Currency::query()->firstOrCreate(
            ['code' => strtoupper((string) config('currency.base_currency'))],
            [
                'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'decimal_places' => 2,
                'exchange_rate' => 1.0,
                'is_active' => true,
            ],
        );

        $this->package = $this->package(validityDays: 90, totalCredits: 8);
    }

    /**
     * PackageFactory already attaches USD and SYP prices on creation, so none are added here —
     * a second row would collide with unique_price_morph_currency.
     */
    private function package(?int $validityDays, int $totalCredits = 8): Package
    {
        return Package::factory()->create([
            'total_credits' => $totalCredits,
            'validity_days' => $validityDays,
        ]);
    }

    /**
     * One class reused for every session in a test. Building a fresh class — and with it a fresh
     * category and instructor — per session exhausts Faker's unique-value pool once a test needs
     * eight or nine of them.
     */
    private function class(): Classes
    {
        return $this->class ??= Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create()->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
                'total_spots' => 20,
            ]));
    }

    private function classSession(Carbon $date): ClassSession
    {
        $class = $this->class();

        return ClassSession::factory()->create([
            'class_id' => $class->id,
            'date' => $date->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
    }

    /**
     * @return list<int>
     */
    private function sessionsWithin(Carbon $purchasedAt, int $count): array
    {
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $ids[] = $this->classSession($purchasedAt->copy()->addDays($i))->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $attended
     * @param  list<int>  $missed
     */
    private function backfill(
        Carbon $purchasedAt,
        array $attended = [],
        array $missed = [],
        ?Package $package = null,
        ?float $rateOverride = null,
        ?int $paidAmount = null,
    ): Booking {
        $plan = $this->validator->validate(
            user: $this->client,
            packageId: ($package ?? $this->package)->id,
            purchasedAt: $purchasedAt,
            currencyId: $this->currency->id,
            paidAmount: $paidAmount,
            attendedSessionIds: $attended,
            missedSessionIds: $missed,
            exchangeRateOverride: $rateOverride,
        );

        return $this->service->backfill($plan, $this->admin->id);
    }

    private function giveClientAnActiveBooking(?Carbon $expiresAt = null): Booking
    {
        return Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'total_credits' => 8,
            'remaining_credits' => 4,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => $expiresAt ?? now()->addMonth(),
        ]);
    }

    // ------------------------------------------------------------- happy paths

    #[Test]
    public function a_fully_consumed_package_backfills_as_exhausted(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 8);

        $booking = $this->backfill($purchasedAt, array_slice($ids, 0, 6), array_slice($ids, 6, 2));

        $this->assertSame(0, $booking->remaining_credits);
        $this->assertSame(BookingStatusEnum::EXHAUSTED, $booking->status);
        $this->assertSame(BookingSourceTypeEnum::HISTORICAL_BACKFILL, $booking->source_type);
        $this->assertSame($this->admin->id, $booking->created_by);
        $this->assertTrue($booking->purchased_at->isSameDay($purchasedAt));

        $this->assertSame(6, $booking->bookingSessions()->where('attendance_status', AttendanceStatusEnum::ATTENDED)->count());
        $this->assertSame(2, $booking->bookingSessions()->where('attendance_status', AttendanceStatusEnum::MISSED)->count());
        $this->assertSame(8, $booking->bookingSessions()->where('status', BookingSessionStatusEnum::RESERVED)->count());
    }

    #[Test]
    public function leftover_credits_on_a_still_valid_package_backfill_as_active(): void
    {
        $purchasedAt = now()->subDays(10)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 6);

        $booking = $this->backfill($purchasedAt, array_slice($ids, 0, 4), array_slice($ids, 4, 2));

        $this->assertSame(2, $booking->remaining_credits);
        $this->assertSame(BookingStatusEnum::ACTIVE, $booking->status);
    }

    #[Test]
    public function a_package_whose_validity_has_elapsed_backfills_as_expired(): void
    {
        $package = $this->package(validityDays: 30);
        $purchasedAt = now()->subMonths(6)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 3);

        $booking = $this->backfill($purchasedAt, $ids, [], $package);

        $this->assertSame(5, $booking->remaining_credits);
        $this->assertSame(BookingStatusEnum::EXPIRED, $booking->status);
    }

    #[Test]
    public function zero_attended_and_zero_missed_creates_a_booking_with_no_sessions(): void
    {
        $booking = $this->backfill(now()->subMonths(6)->startOfDay());

        $this->assertSame(8, $booking->remaining_credits);
        $this->assertSame(0, $booking->bookingSessions()->count());
    }

    #[Test]
    public function created_at_stays_the_entry_timestamp_while_purchased_at_holds_the_paper_date(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();

        $booking = $this->backfill($purchasedAt)->fresh();

        $this->assertTrue($booking->purchased_at->isSameDay($purchasedAt));
        $this->assertTrue($booking->created_at->isToday());
        $this->assertTrue($booking->expires_at->isSameDay($purchasedAt->copy()->addDays(90)));
    }

    #[Test]
    public function attended_at_records_the_moment_the_class_ran_not_the_entry_time(): void
    {
        // D-A15: the business date for a booking_session is the class session's own date.
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 1);

        $this->backfill($purchasedAt, $ids);

        $session = BookingSession::query()->firstOrFail();
        $classDate = ClassSession::findOrFail($ids[0])->date;

        $this->assertTrue(
            $session->attended_at->isSameDay($classDate),
            'attended_at must fall on the class date, not today.',
        );
        $this->assertSame('09:00', $session->attended_at->format('H:i'));
        $this->assertSame($this->admin->id, $session->attendance_updated_by);
    }

    #[Test]
    public function a_missed_session_has_no_attended_at(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 1);

        $this->backfill($purchasedAt, [], $ids);

        $this->assertNull(BookingSession::query()->firstOrFail()->attended_at);
    }

    // -------------------------------------------------------- D-A01 rejection

    #[Test]
    public function it_rejects_backfill_when_user_has_active_booking_and_old_package_is_still_valid(): void
    {
        $this->giveClientAnActiveBooking();

        $purchasedAt = now()->subDays(10)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 2);

        try {
            $this->backfill($purchasedAt, $ids);
            $this->fail('Expected a ValidationException, not a successful write.');
        } catch (QueryException) {
            $this->fail('The validator must reject this before the database sees it.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('package_id', $e->errors());
        }

        $this->assertSame(
            0,
            Booking::where('source_type', BookingSourceTypeEnum::HISTORICAL_BACKFILL)->count(),
            'A rejected backfill must write nothing.',
        );
        $this->assertSame(0, BookingSession::count());
    }

    #[Test]
    public function it_rejects_backfill_when_the_conflicting_booking_is_stale(): void
    {
        // Regression sentinel for D-A01 §C1. This booking is past expires_at but still says
        // active with credits left, so it is invisible to every expiry-filtered helper yet still
        // occupies active_user_id.
        //
        // Asserts against the VALIDATOR directly, not the full backfill. Going through the
        // service cannot tell the two layers apart: an expiry-filtered guard would let the write
        // proceed, the database would raise 1062, and the service's safety net would convert it
        // into the very same ValidationException. The requirement is that the database never sees
        // the conflict at all.
        $this->giveClientAnActiveBooking(expiresAt: now()->subMonth());

        $purchasedAt = now()->subDays(10)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 2);

        $this->expectException(ValidationException::class);

        $this->validator->validate(
            user: $this->client,
            packageId: $this->package->id,
            purchasedAt: $purchasedAt,
            currencyId: $this->currency->id,
            paidAmount: null,
            attendedSessionIds: $ids,
            missedSessionIds: [],
        );
    }

    #[Test]
    public function the_validator_rejects_a_live_conflict_before_any_write(): void
    {
        $this->giveClientAnActiveBooking();

        $purchasedAt = now()->subDays(10)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 2);

        $this->expectException(ValidationException::class);

        $this->validator->validate(
            user: $this->client,
            packageId: $this->package->id,
            purchasedAt: $purchasedAt,
            currencyId: $this->currency->id,
            paidAmount: null,
            attendedSessionIds: $ids,
            missedSessionIds: [],
        );
    }

    #[Test]
    public function an_exhausted_backfill_is_allowed_even_when_an_active_booking_exists(): void
    {
        // remaining_credits = 0 makes the generated column NULL, so there is no conflict.
        $this->giveClientAnActiveBooking();

        $purchasedAt = now()->subDays(10)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 8);

        $booking = $this->backfill($purchasedAt, $ids);

        $this->assertSame(BookingStatusEnum::EXHAUSTED, $booking->status);
    }

    // -------------------------------------------------------- D-A02 rejection

    #[Test]
    public function it_rejects_backfill_for_null_validity_package(): void
    {
        $this->expectException(ValidationException::class);

        $this->backfill(now()->subMonths(2)->startOfDay(), package: $this->package(validityDays: null));
    }

    #[Test]
    public function it_rejects_backfill_for_zero_validity_package(): void
    {
        // Sentinel: fails if the gate is written as `=== null` instead of `> 0`. validity_days = 0
        // is reachable and renders to admins as "Unlimited".
        $this->expectException(ValidationException::class);

        $this->backfill(now()->subMonths(2)->startOfDay(), package: $this->package(validityDays: 0));
    }

    #[Test]
    public function the_validity_gate_runs_before_the_count_checks(): void
    {
        // Categorical exclusion: even a fully-consumed null-validity package is refused, and the
        // refusal names the package, not the counts.
        $package = $this->package(validityDays: null);

        try {
            $this->backfill(now()->subMonths(2)->startOfDay(), package: $package);
            $this->fail('Expected rejection.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('package_id', $e->errors());
        }
    }

    // ------------------------------------------------------------- validation

    #[Test]
    public function it_rejects_a_future_purchase_date(): void
    {
        $this->expectException(ValidationException::class);

        $this->backfill(now()->addDay());
    }

    #[Test]
    public function it_rejects_when_a_session_is_both_attended_and_missed(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 2);

        $this->expectException(ValidationException::class);

        $this->backfill($purchasedAt, $ids, [$ids[0]]);
    }

    #[Test]
    public function it_rejects_when_selections_exceed_the_package_credits(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 9);

        $this->expectException(ValidationException::class);

        $this->backfill($purchasedAt, $ids);
    }

    #[Test]
    public function it_rejects_a_session_outside_the_validity_window(): void
    {
        $purchasedAt = now()->subMonths(6)->startOfDay();
        $outside = $this->classSession($purchasedAt->copy()->addDays(200))->id;

        $this->expectException(ValidationException::class);

        $this->backfill($purchasedAt, [$outside]);
    }

    #[Test]
    public function it_rejects_a_cancelled_session(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $session = $this->classSession($purchasedAt->copy()->addDay());
        $session->update(['status' => ClassSessionStatusEnum::CANCELLED->value]);

        $this->expectException(ValidationException::class);

        $this->backfill($purchasedAt, [$session->id]);
    }

    #[Test]
    public function it_rejects_a_session_the_client_is_already_recorded_in(): void
    {
        // A07: the DB unique is (booking_id, class_session_id) and cannot see across bookings.
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 1);

        $this->backfill($purchasedAt, $ids);

        $this->expectException(ValidationException::class);

        $this->backfill($purchasedAt->copy()->subDay(), $ids);
    }

    // --------------------------------------------------------------- D-A03

    #[Test]
    public function it_uses_admin_provided_exchange_rate_when_present(): void
    {
        $booking = $this->backfill(now()->subMonths(2)->startOfDay(), rateOverride: 1234.5);

        $this->assertSame(1234.5, (float) $booking->fresh()->exchange_rate_snapshot);
    }

    #[Test]
    public function it_uses_current_rate_when_exchange_rate_snapshot_is_null(): void
    {
        $booking = $this->backfill(now()->subMonths(2)->startOfDay());

        $expected = app(PricingService::class)
            ->getExchangeRateForSnapshot($this->currency->id);

        $this->assertSame($expected, (float) $booking->fresh()->exchange_rate_snapshot);
    }

    // ------------------------------------------------------------- integrity

    #[Test]
    public function the_credit_arithmetic_invariant_holds(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 5);

        $booking = $this->backfill($purchasedAt, array_slice($ids, 0, 3), array_slice($ids, 3, 2));

        $attended = $booking->bookingSessions()->where('attendance_status', AttendanceStatusEnum::ATTENDED)->count();
        $missed = $booking->bookingSessions()->where('attendance_status', AttendanceStatusEnum::MISSED)->count();

        $this->assertSame(
            $booking->total_credits,
            $attended + $missed + $booking->remaining_credits,
        );
    }

    #[Test]
    public function a_rejected_write_leaves_no_partial_rows(): void
    {
        // The duplicate check fires mid-transaction, after sessions are locked. Nothing may
        // survive the rollback.
        $purchasedAt = now()->subMonths(2)->startOfDay();
        $ids = $this->sessionsWithin($purchasedAt, 2);

        $this->backfill($purchasedAt, [$ids[0]]);

        $bookingsBefore = Booking::count();
        $sessionsBefore = BookingSession::count();

        try {
            $this->backfill($purchasedAt, [$ids[1]], [$ids[0]]);
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame($bookingsBefore, Booking::count());
        $this->assertSame($sessionsBefore, BookingSession::count());
    }

    #[Test]
    public function a_backfilled_booking_is_absent_from_todays_balance(): void
    {
        $purchasedAt = now()->subMonths(2)->startOfDay();

        $this->backfill($purchasedAt);

        $summary = app(DailyBalanceService::class)
            ->getSummary(now()->toDateString())
            ->firstWhere('currencyId', $this->currency->id);

        $this->assertSame(0, $summary->packageRevenue);
    }
}
