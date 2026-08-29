<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSourceTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
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
use App\Services\Currency\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Historical entries through POST /admin/operations/packages/{packageId}/assign.
 *
 * The presence of `purchased_at` is what routes the request down the backfill path; without it
 * the same endpoint performs an ordinary live assignment.
 *
 * @see docs/historical-backfill/plan/phase-4-submit-endpoint.md
 */
final class HistoricalBackfillEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private Currency $currency;

    private Package $package;

    private Carbon $purchasedAt;

    private ?Classes $class = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);
        $this->client = User::factory()->create();

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

        $this->package = Package::factory()->create(['validity_days' => 90, 'total_credits' => 8]);
        $this->purchasedAt = now()->subMonths(2)->startOfDay();
    }

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

    /**
     * @return list<int>
     */
    private function sessions(int $count): array
    {
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $ids[] = ClassSession::factory()->create([
                'class_id' => $this->class()->id,
                'date' => $this->purchasedAt->copy()->addDays($i)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
            ])->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function uri(?int $packageId = null): string
    {
        return '/admin/operations/packages/' . ($packageId ?? $this->package->id) . '/assign';
    }

    private function payload(array $overrides = []): array
    {
        return [
            'user_id' => $this->client->id,
            'purchased_at' => $this->purchasedAt->toDateString(),
            'currency_id' => $this->currency->id,
            'attended_session_ids' => [],
            'missed_session_ids' => [],
            'idempotency_key' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): TestResponse
    {
        // package_id is a route parameter on the assign endpoint, so a test that wants a
        // different package says so via the URL rather than the body.
        $packageId = $overrides['package_id'] ?? null;
        unset($overrides['package_id']);

        return $this->actingAs($this->admin)
            ->postJson($this->uri($packageId), $this->payload($overrides));
    }

    // ----------------------------------------------------------------- access

    #[Test]
    public function it_is_unreachable_without_authentication(): void
    {
        $this->postJson($this->uri(), $this->payload())->assertUnauthorized();
    }

    // ------------------------------------------------------------ happy path

    #[Test]
    public function a_valid_payload_creates_one_booking_and_its_sessions(): void
    {
        $ids = $this->sessions(8);

        $this->submit([
            'attended_session_ids' => array_slice($ids, 0, 6),
            'missed_session_ids' => array_slice($ids, 6, 2),
        ])->assertCreated();

        $booking = Booking::query()
            ->where('source_type', BookingSourceTypeEnum::HISTORICAL_BACKFILL)
            ->sole();

        $this->assertSame($this->client->id, $booking->user_id);
        $this->assertSame($this->admin->id, $booking->created_by);
        $this->assertSame(0, $booking->remaining_credits);
        $this->assertSame(BookingStatusEnum::EXHAUSTED, $booking->status);
        $this->assertTrue($booking->purchased_at->isSameDay($this->purchasedAt));

        $this->assertSame(8, BookingSession::count());
        $this->assertSame(6, BookingSession::where('attendance_status', AttendanceStatusEnum::ATTENDED)->count());
        $this->assertSame(2, BookingSession::where('attendance_status', AttendanceStatusEnum::MISSED)->count());
    }

    #[Test]
    public function it_accepts_a_backfill_with_no_sessions_selected(): void
    {
        $this->submit()->assertCreated();

        $this->assertSame(8, Booking::sole()->remaining_credits);
        $this->assertSame(0, BookingSession::count());
    }

    // ----------------------------------------------------------- idempotency

    #[Test]
    public function a_replayed_token_is_rejected_without_a_second_write(): void
    {
        $payload = $this->payload();

        $this->actingAs($this->admin)->postJson($this->uri(), $payload)->assertCreated();

        $this->actingAs($this->admin)->postJson($this->uri(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');

        $this->assertSame(1, Booking::count());
    }

    #[Test]
    public function distinct_tokens_each_create_their_own_booking(): void
    {
        // Exhausted so the second one cannot trip the active-booking constraint.
        $first = $this->sessions(8);
        $this->submit(['attended_session_ids' => $first])->assertCreated();

        $this->purchasedAt = now()->subMonths(6)->startOfDay();
        $second = $this->sessions(8);
        $this->submit([
            'purchased_at' => $this->purchasedAt->toDateString(),
            'attended_session_ids' => $second,
        ])->assertCreated();

        $this->assertSame(2, Booking::count());
    }

    #[Test]
    public function a_rejected_submission_does_not_burn_its_token(): void
    {
        // A conflict is something the admin fixes and resends. Consuming the key on failure would
        // refuse the corrected retry as a duplicate of a write that never happened.
        $key = (string) Str::uuid();
        $ids = $this->sessions(2);

        Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'remaining_credits' => 4,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->addMonth(),
        ]);

        $this->submit([
            'purchased_at' => now()->subDays(5)->toDateString(),
            'attended_session_ids' => $ids,
            'idempotency_key' => $key,
        ])->assertStatus(422);

        // Same key, now with a selection that exhausts the package, so no conflict remains.
        $this->purchasedAt = now()->subMonths(4)->startOfDay();
        $this->submit([
            'purchased_at' => $this->purchasedAt->toDateString(),
            'attended_session_ids' => $this->sessions(8),
            'idempotency_key' => $key,
        ])->assertCreated();
    }

    #[Test]
    public function the_token_must_be_a_uuid(): void
    {
        $this->submit(['idempotency_key' => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');
    }

    #[Test]
    public function the_token_is_required(): void
    {
        $payload = $this->payload();
        unset($payload['idempotency_key']);

        $this->actingAs($this->admin)->postJson($this->uri(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');
    }

    // ------------------------------------------------------- exchange rate

    #[Test]
    public function a_supplied_rate_is_stored_verbatim(): void
    {
        $this->submit(['exchange_rate_snapshot' => 1234.5])->assertCreated();

        $this->assertSame(1234.5, (float) Booking::sole()->exchange_rate_snapshot);
    }

    #[Test]
    public function an_omitted_rate_falls_back_to_the_current_rate(): void
    {
        $this->submit()->assertCreated();

        $expected = app(PricingService::class)->getExchangeRateForSnapshot($this->currency->id);

        $this->assertSame($expected, (float) Booking::sole()->exchange_rate_snapshot);
    }

    #[Test]
    public function a_zero_rate_is_rejected(): void
    {
        // Guards the falsy-zero hazard: the observer recomputes whenever the snapshot is falsy,
        // so a stored 0 would let a later save overwrite the historical rate with today's.
        $this->submit(['exchange_rate_snapshot' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('exchange_rate_snapshot');
    }

    // ------------------------------------------------------ domain rejection

    #[Test]
    public function a_conflicting_active_booking_returns_422_with_a_field_keyed_message(): void
    {
        // Must not be flattened into a bare 500 or a message-only 422: the modal attaches this to
        // the package field and the text names the blocking package.
        Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'remaining_credits' => 4,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->addMonth(),
        ]);

        $this->purchasedAt = now()->subDays(5)->startOfDay();

        $response = $this->submit([
            'purchased_at' => $this->purchasedAt->toDateString(),
            'attended_session_ids' => $this->sessions(2),
        ])->assertStatus(422)->assertJsonValidationErrors('package_id');

        $this->assertStringContainsString(
            $this->client->fullname,
            $response->json('errors.package_id.0'),
            'The rejection must name the client.',
        );

        $this->assertSame(
            0,
            Booking::where('source_type', BookingSourceTypeEnum::HISTORICAL_BACKFILL)->count(),
        );
    }

    #[Test]
    public function an_unlimited_package_is_rejected(): void
    {
        $package = Package::factory()->create(['validity_days' => null]);

        $this->submit(['package_id' => $package->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('package_id');
    }

    #[Test]
    public function a_future_purchase_date_is_rejected(): void
    {
        $this->submit(['purchased_at' => now()->addDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('purchased_at');
    }

    #[Test]
    public function a_session_in_both_lists_is_rejected(): void
    {
        $ids = $this->sessions(2);

        $this->submit([
            'attended_session_ids' => $ids,
            'missed_session_ids' => [$ids[0]],
        ])->assertStatus(422);

        $this->assertSame(0, Booking::count());
    }

    #[Test]
    public function selecting_more_sessions_than_the_package_holds_is_rejected(): void
    {
        $this->submit(['attended_session_ids' => $this->sessions(9)])->assertStatus(422);

        $this->assertSame(0, Booking::count());
    }

    #[Test]
    public function a_repeated_id_within_one_list_is_rejected(): void
    {
        $ids = $this->sessions(1);

        $this->submit(['attended_session_ids' => [$ids[0], $ids[0]]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attended_session_ids.0');
    }

    #[Test]
    public function an_unknown_session_id_is_rejected(): void
    {
        $this->submit(['attended_session_ids' => [999_999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attended_session_ids.0');
    }

    #[Test]
    public function omitting_the_session_lists_records_a_backfill_with_no_sessions(): void
    {
        // Deliberate change from the dedicated endpoint, which required both keys to be present.
        // Sharing rules with the live-sale path rules that out, and `required` counts [] as empty
        // anyway — so it would have rejected the zero-attended, zero-missed entry that is
        // explicitly supported. Absent now means none selected.
        $payload = $this->payload();
        unset($payload['attended_session_ids'], $payload['missed_session_ids']);

        $this->actingAs($this->admin)->postJson($this->uri(), $payload)->assertCreated();

        $this->assertSame(8, Booking::sole()->remaining_credits);
        $this->assertSame(0, BookingSession::count());
    }

    // ------------------------------------------------------- the discriminator

    #[Test]
    public function session_ids_without_a_purchase_date_are_rejected(): void
    {
        // The one failure mode that would otherwise SUCCEED: with no date this reads as an
        // ordinary live assignment, the selections are dropped, and the admin is told the package
        // was assigned.
        $ids = $this->sessions(2);
        $payload = $this->payload(['attended_session_ids' => $ids]);
        unset($payload['purchased_at']);

        $this->actingAs($this->admin)->postJson($this->uri(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('purchased_at');

        $this->assertSame(0, Booking::count());
    }

    #[Test]
    public function an_idempotency_key_without_a_purchase_date_is_rejected(): void
    {
        $payload = $this->payload();
        unset($payload['purchased_at']);

        $this->actingAs($this->admin)->postJson($this->uri(), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('purchased_at');
    }

    // ------------------------------------------------------------ live sale

    #[Test]
    public function omitting_every_historical_field_performs_an_ordinary_assignment(): void
    {
        $this->actingAs($this->admin)->postJson($this->uri(), [
            'user_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ])->assertCreated();

        $booking = Booking::sole();

        $this->assertSame(BookingSourceTypeEnum::STANDARD, $booking->source_type);
        $this->assertSame(BookingStatusEnum::ACTIVE, $booking->status);
        $this->assertSame(8, $booking->remaining_credits);
        $this->assertTrue($booking->purchased_at->isToday());
        $this->assertSame(0, BookingSession::count());
    }

    #[Test]
    public function a_live_assignment_is_refused_for_a_frozen_account(): void
    {
        // isFrozen() reads the status column, not frozen_at.
        $this->client->update(['status' => UserStatusEnum::FROZEN->value]);

        $this->actingAs($this->admin)->postJson($this->uri(), [
            'user_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ])->assertStatus(422)->assertJsonValidationErrors('user_id');

        $this->assertSame(0, Booking::count());
    }

    #[Test]
    public function a_live_assignment_is_refused_when_a_frozen_booking_exists(): void
    {
        Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'status' => BookingStatusEnum::FROZEN->value,
        ]);

        $this->actingAs($this->admin)->postJson($this->uri(), [
            'user_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ])->assertStatus(422)->assertJsonValidationErrors('user_id');
    }

    #[Test]
    public function a_historical_entry_is_refused_when_a_frozen_booking_exists(): void
    {
        // The frozen guards used to apply only to live sales. Both paths share them now.
        Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'status' => BookingStatusEnum::FROZEN->value,
        ]);

        $this->submit()->assertStatus(422)->assertJsonValidationErrors('user_id');
    }

    #[Test]
    public function a_stale_active_booking_is_reconciled_and_no_longer_blocks(): void
    {
        // This row is past its expiry but still marked active, so it was holding
        // unique_active_booking_per_user while being useless to every feature that reads it.
        // Nothing expires bookings on a schedule, so these accumulate. Assignment now reconciles
        // it — recording a state change that already happened — instead of refusing forever.
        $stale = Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'remaining_credits' => 3,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->subMonth(),
        ]);

        try {
            $this->actingAs($this->admin)->postJson($this->uri(), [
                'user_id' => $this->client->id,
                'currency_id' => $this->currency->id,
            ])->assertCreated();
        } catch (QueryException $e) {
            $this->fail('The guard must resolve this before the database sees it: ' . $e->getMessage());
        }

        $this->assertSame(BookingStatusEnum::EXPIRED, $stale->fresh()->status);
        $this->assertSame(2, Booking::count());
    }

    #[Test]
    public function a_valid_active_booking_still_blocks_a_live_assignment(): void
    {
        // The other half of the same rule: reconciliation only touches rows whose expiry has
        // passed. A booking with credits and a future expiry is untouched and still blocks —
        // this is what stops it becoming the "supersede" behaviour that destroys paid credits.
        $active = Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'remaining_credits' => 3,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->admin)->postJson($this->uri(), [
            'user_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ])->assertStatus(422)->assertJsonValidationErrors('user_id');

        $this->assertSame(BookingStatusEnum::ACTIVE, $active->fresh()->status);
        $this->assertSame(3, $active->fresh()->remaining_credits);
        $this->assertSame(1, Booking::count());
    }

    #[Test]
    public function an_unlimited_package_still_assigns_on_the_live_path(): void
    {
        // D-A02 blocks unlimited packages for historical entries only; it must not leak across.
        $package = Package::factory()->create(['validity_days' => null, 'total_credits' => 8]);

        $this->actingAs($this->admin)->postJson($this->uri($package->id), [
            'user_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ])->assertCreated();

        $this->assertNull(Booking::sole()->expires_at);
    }
}
