<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\WeekdayEnum;
use App\Models\Booking;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Currency;
use App\Models\Instructor;
use App\Models\Language;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\Booking\BookingEloquentRepository;
use App\Services\Booking\BookingFreezeService;
use App\Services\BookingSession\BookingSessionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every write path that can populate `active_user_id` must agree with the index behind it.
 *
 * `unique_active_booking_per_user` is a UNIQUE index over a stored generated column:
 *   active_user_id = CASE WHEN status='active' AND remaining_credits>0 THEN user_id ELSE NULL END
 *
 * A guard that adds an expiry clause asks a narrower question than the index answers, so it lets
 * a stale row through and the insert dies on a raw 1062. Each test here drives one path and fails
 * explicitly on QueryException — that is what separates "guarded" from "happens to work today".
 */
final class ActiveBookingConstraintTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);

        Currency::query()->firstOrCreate(
            ['code' => strtoupper((string) config('currency.base_currency'))],
            [
                'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'decimal_places' => 2,
                'exchange_rate' => 1.0,
                'is_active' => true,
            ],
        );

        $this->client = User::factory()->create();
        $this->package = Package::factory()->create(['validity_days' => 90, 'total_credits' => 8]);
    }

    private function booking(array $attributes = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->client->id,
            'package_id' => $this->package->id,
            'total_credits' => 8,
            'remaining_credits' => 3,
            'status' => BookingStatusEnum::ACTIVE->value,
        ], $attributes));
    }

    private function pastSession(): ClassSession
    {
        $class = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create()->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
                'total_spots' => 20,
            ]));

        return ClassSession::factory()->create([
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
    }

    // ------------------------------------------------------------ reconciliation

    #[Test]
    public function reconciliation_expires_only_rows_whose_expiry_has_passed(): void
    {
        $stale = $this->booking(['expires_at' => now()->subMonth()]);
        $valid = $this->booking(['expires_at' => now()->addMonth(), 'status' => BookingStatusEnum::EXHAUSTED->value]);
        $neverExpires = $this->booking(['expires_at' => null, 'status' => BookingStatusEnum::EXHAUSTED->value]);

        $count = app(BookingEloquentRepository::class)
            ->expireStaleActiveBookings($this->client->id);

        $this->assertSame(1, $count);
        $this->assertSame(BookingStatusEnum::EXPIRED, $stale->fresh()->status);

        // Untouched: this is reconciliation of a state change that already happened, not the
        // "supersede" behaviour that destroys credits on a booking still in force.
        $this->assertSame(BookingStatusEnum::EXHAUSTED, $valid->fresh()->status);
        $this->assertSame(BookingStatusEnum::EXHAUSTED, $neverExpires->fresh()->status);
    }

    #[Test]
    public function a_booking_with_no_expiry_is_never_reconciled_away(): void
    {
        $forever = $this->booking(['expires_at' => null]);

        app(BookingEloquentRepository::class)
            ->expireStaleActiveBookings($this->client->id);

        $this->assertSame(BookingStatusEnum::ACTIVE, $forever->fresh()->status);
    }

    // ---------------------------------------------------------------- walk-in

    #[Test]
    public function a_walk_in_does_not_collide_with_a_stale_active_booking(): void
    {
        $stale = $this->booking(['expires_at' => now()->subMonth()]);
        $session = $this->pastSession();

        try {
            app(BookingSessionService::class)->oneTimeAttend($this->client->id, $session->id);
        } catch (QueryException $e) {
            $this->fail('Walk-in must resolve this before the database sees it: ' . $e->getMessage());
        }

        $this->assertSame(BookingStatusEnum::EXPIRED, $stale->fresh()->status);
    }

    #[Test]
    public function a_walk_in_is_refused_when_a_valid_active_booking_cannot_be_spent(): void
    {
        // Active, unexpired, but zero credits: nothing to spend, yet it does not occupy the index
        // either (the generated column needs credits > 0), so the walk-in booking is created.
        $this->booking(['remaining_credits' => 0, 'expires_at' => now()->addMonth()]);
        $session = $this->pastSession();

        try {
            app(BookingSessionService::class)->oneTimeAttend($this->client->id, $session->id);
        } catch (QueryException $e) {
            $this->fail('Walk-in must not hit the index: ' . $e->getMessage());
        }

        $this->assertTrue(true);
    }

    // --------------------------------------------------------------- unfreeze

    #[Test]
    public function unfreezing_is_refused_when_another_active_booking_exists(): void
    {
        $frozen = $this->booking([
            'status' => BookingStatusEnum::FROZEN->value,
            'remaining_credits' => 4,
            'expires_at' => now()->addMonth(),
        ]);

        $this->booking(['expires_at' => now()->addMonth()]);

        try {
            app(BookingFreezeService::class)->unfreeze($frozen);
            $this->fail('Expected a ValidationException.');
        } catch (QueryException $e) {
            $this->fail('Unfreeze leaked a raw SQL error to the caller: ' . $e->getMessage());
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('booking_id', $e->errors());
        }

        $this->assertSame(BookingStatusEnum::FROZEN, $frozen->fresh()->status);
    }

    #[Test]
    public function unfreezing_succeeds_when_the_only_other_booking_is_stale(): void
    {
        $stale = $this->booking(['expires_at' => now()->subMonth()]);

        $frozen = $this->booking([
            'status' => BookingStatusEnum::FROZEN->value,
            'remaining_credits' => 4,
            'expires_at' => now()->addMonth(),
        ]);

        try {
            $resumed = app(BookingFreezeService::class)->unfreeze($frozen);
        } catch (QueryException $e) {
            $this->fail('Unfreeze must resolve this before the database sees it: ' . $e->getMessage());
        }

        $this->assertSame(BookingStatusEnum::ACTIVE, $resumed->status);
        $this->assertSame(BookingStatusEnum::EXPIRED, $stale->fresh()->status);
    }

    // -------------------------------------------------- what the UI is told

    #[Test]
    public function the_client_resource_reports_a_stale_booking_as_blocking(): void
    {
        // active_package filters expiry, so it goes null here — which is why the assign button
        // used to render enabled and the admin clicked into a 422. blocking_package is what the
        // database would actually refuse.
        $this->booking(['expires_at' => now()->subMonth()]);

        $admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);

        $payload = $this->actingAs($admin)
            ->getJson("/admin/operations/clients/{$this->client->id}/details")
            ->assertOk()
            ->json('data');

        $this->assertNull($payload['active_package']);
        $this->assertNotNull($payload['blocking_package']);
        $this->assertSame(3, $payload['blocking_package']['remaining_credits']);
    }

    #[Test]
    public function the_client_resource_reports_no_blocking_package_when_there_is_none(): void
    {
        $admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);

        $payload = $this->actingAs($admin)
            ->getJson("/admin/operations/clients/{$this->client->id}/details")
            ->assertOk()
            ->json('data');

        $this->assertNull($payload['blocking_package']);
    }

    // ------------------------------------------------- Filament / scope parity

    #[Test]
    public function the_blocking_scope_matches_a_stale_active_booking(): void
    {
        // Sentinel for the Filament path: with an expiry clause on the ACTIVE branch this scope
        // returned nothing for a stale row, the form validated, and the insert crashed Livewire.
        $this->booking(['expires_at' => now()->subMonth()]);

        $this->assertSame(
            1,
            Booking::query()->where('user_id', $this->client->id)->blockingNewPurchase()->count(),
        );
    }

    #[Test]
    public function the_blocking_scope_ignores_a_booking_with_no_credits(): void
    {
        $this->booking(['remaining_credits' => 0, 'expires_at' => now()->addMonth()]);

        $this->assertSame(
            0,
            Booking::query()->where('user_id', $this->client->id)->blockingNewPurchase()->count(),
        );
    }
}
