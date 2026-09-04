<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\WeekdayEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 3 — the historical backfill session picker.
 *
 * @see docs/historical-backfill/plan/phase-3-session-picker-endpoint.md
 */
final class BackfillSessionPickerTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/admin/operations/bookings/backfill/sessions';

    private User $admin;

    private Package $package;

    private ?Classes $class = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);
        $this->package = Package::factory()->create(['validity_days' => 90, 'total_credits' => 8]);
    }

    private function class(): Classes
    {
        return $this->class ??= Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create(['name' => 'Layla'])->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
                'total_spots' => 12,
            ]));
    }

    private function pastSession(Carbon|string $date, ?string $status = null, string $start = '09:00:00'): ClassSession
    {
        return ClassSession::factory()->create([
            'class_id' => $this->class()->id,
            'date' => $date instanceof Carbon ? $date->toDateString() : $date,
            'start_time' => $start,
            'end_time' => '10:00:00',
            'status' => $status ?? ClassSessionStatusEnum::SCHEDULED->value,
            // class_sessions.total_spots is its own snapshot, taken from the class at generation
            // time — not a live read of classes.total_spots. Pinned here so the assertion is not
            // at the mercy of the factory default.
            'total_spots' => 12,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fetch(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->getJson(self::URI . '?' . http_build_query([
            'package_id' => $this->package->id,
            'purchased_at' => now()->subDays(60)->toDateString(),
            ...$overrides,
        ]));
    }

    // ----------------------------------------------------------------- access

    #[Test]
    public function it_is_unreachable_without_authentication(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }

    // ------------------------------------------------------------- filtering

    #[Test]
    public function it_returns_past_sessions_inside_the_validity_window(): void
    {
        $this->pastSession(now()->subDays(30));
        $this->pastSession(now()->subDays(20), start: '11:00:00');

        $response = $this->fetch()->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function it_excludes_sessions_that_have_not_happened_yet(): void
    {
        $this->pastSession(now()->subDays(10));
        $this->pastSession(now()->addDays(5));

        $response = $this->fetch()->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_excludes_cancelled_sessions(): void
    {
        $this->pastSession(now()->subDays(30));
        $this->pastSession(now()->subDays(25), status: ClassSessionStatusEnum::CANCELLED->value, start: '11:00:00');

        $response = $this->fetch()->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_includes_scheduled_sessions_that_were_never_marked_completed(): void
    {
        // Sentinel for A14: nothing in this system ever writes `completed`, so a picker filtering
        // on that status would return an empty list for every real database.
        $this->pastSession(now()->subDays(30), status: ClassSessionStatusEnum::SCHEDULED->value);

        $response = $this->fetch()->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_excludes_sessions_outside_the_validity_window(): void
    {
        $purchasedAt = now()->subDays(60);

        $this->pastSession($purchasedAt->copy()->addDays(10));
        // Package validity is 90 days, so this one predates the purchase.
        $this->pastSession($purchasedAt->copy()->subDays(10), start: '11:00:00');

        $response = $this->fetch(['purchased_at' => $purchasedAt->toDateString()])->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_can_narrow_to_a_single_month(): void
    {
        $target = now()->subMonths(1)->startOfMonth()->addDays(3);

        $this->pastSession($target);
        $this->pastSession(now()->subDays(2), start: '11:00:00');

        $response = $this->fetch([
            'purchased_at' => now()->subDays(80)->toDateString(),
            'month' => $target->month,
            'year' => $target->year,
        ])->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($target->toDateString(), $response->json('data.0.date'));
    }

    #[Test]
    public function it_excludes_sessions_the_client_is_already_recorded_in(): void
    {
        // A07: the write path refuses these, so offering them would let an admin build a
        // selection guaranteed to fail on submit.
        $client = User::factory()->create();
        $taken = $this->pastSession(now()->subDays(30));
        $this->pastSession(now()->subDays(25), start: '11:00:00');

        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'package_id' => $this->package->id,
        ]);

        BookingSession::factory()->create([
            'booking_id' => $booking->id,
            'class_session_id' => $taken->id,
            'status' => BookingSessionStatusEnum::RESERVED->value,
            'attendance_status' => AttendanceStatusEnum::ATTENDED->value,
        ]);

        $response = $this->fetch(['user_id' => $client->id])->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertNotSame($taken->id, $response->json('data.0.id'));
    }

    // ------------------------------------------------------------- behaviour

    #[Test]
    public function it_rejects_a_package_with_no_validity_window(): void
    {
        // Same D-A02 gate as the write path; an unlimited package has no window to page.
        $package = Package::factory()->create(['validity_days' => null]);

        $this->fetch(['package_id' => $package->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('package_id');
    }

    #[Test]
    public function it_rejects_a_future_purchase_date(): void
    {
        $this->fetch(['purchased_at' => now()->addDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('purchased_at');
    }

    #[Test]
    public function it_orders_sessions_chronologically(): void
    {
        $this->pastSession(now()->subDays(10), start: '08:00:00');
        $this->pastSession(now()->subDays(30));
        $this->pastSession(now()->subDays(10), start: '07:00:00');

        // Display strings like "9:00 AM" do not sort lexically, so the expected
        // order must come from the raw columns the query actually orders by.
        $responseIds = collect($this->fetch()->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $expectedIds = ClassSession::query()
            ->orderBy('date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame($expectedIds, $responseIds);
    }

    #[Test]
    public function it_returns_the_fields_the_picker_renders(): void
    {
        $session = $this->pastSession(now()->subDays(30));

        $row = $this->fetch()->assertOk()->json('data.0');

        $this->assertSame($session->id, $row['id']);
        $this->assertSame($session->date->toDateString(), $row['date']);
        $this->assertSame('9:00 AM', $row['start_time']);
        $this->assertSame('10:00 AM', $row['end_time']);
        $this->assertSame('Layla', $row['instructor_name']);
        $this->assertNotNull($row['class_title']);
        $this->assertSame(12, $row['total_spots']);
        $this->assertSame(0, $row['reserved_count']);
    }

    #[Test]
    public function reserved_count_reflects_existing_reservations(): void
    {
        $session = $this->pastSession(now()->subDays(30));
        $booking = Booking::factory()->create(['package_id' => $this->package->id]);

        BookingSession::factory()->create([
            'booking_id' => $booking->id,
            'class_session_id' => $session->id,
            'status' => BookingSessionStatusEnum::RESERVED->value,
        ]);

        $this->assertSame(1, $this->fetch()->assertOk()->json('data.0.reserved_count'));
    }

    #[Test]
    public function it_reports_the_derived_window(): void
    {
        $purchasedAt = now()->subDays(60)->startOfDay();

        $meta = $this->fetch(['purchased_at' => $purchasedAt->toDateString()])->assertOk()->json('meta');

        $this->assertSame($purchasedAt->toDateString(), $meta['window']['from']);
        $this->assertSame($purchasedAt->copy()->addDays(90)->toDateString(), $meta['window']['to']);
    }

    // ------------------------------------------------------------ pagination

    #[Test]
    public function it_pages_through_results_with_a_cursor(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->pastSession(now()->subDays(40 - $i));
        }

        $first = $this->fetch(['per_page' => 2])->assertOk();

        $this->assertCount(2, $first->json('data'));
        $this->assertTrue($first->json('meta.has_more'));
        $this->assertNotNull($first->json('meta.next_cursor'));

        $seen = collect($first->json('data'))->pluck('id')->all();
        $cursor = $first->json('meta.next_cursor');

        while ($cursor !== null) {
            $page = $this->fetch(['per_page' => 2, 'cursor' => $cursor])->assertOk();
            $seen = [...$seen, ...collect($page->json('data'))->pluck('id')->all()];
            $cursor = $page->json('meta.next_cursor');
        }

        $this->assertCount(5, $seen, 'Every session must appear exactly once across the pages.');
        $this->assertCount(5, array_unique($seen));
    }

    #[Test]
    public function has_more_is_false_on_the_final_page(): void
    {
        $this->pastSession(now()->subDays(30));
        $this->pastSession(now()->subDays(25), start: '11:00:00');

        $response = $this->fetch(['per_page' => 5])->assertOk();

        $this->assertFalse($response->json('meta.has_more'));
        $this->assertNull($response->json('meta.next_cursor'));
    }

    #[Test]
    public function sessions_sharing_a_date_and_time_still_page_deterministically(): void
    {
        // (date, start_time) is not unique across classes, so the cursor needs the id tiebreaker
        // or rows silently repeat or vanish between pages.
        $otherClass = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create()->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
                'total_spots' => 10,
            ]));

        $date = now()->subDays(30)->toDateString();

        $a = $this->pastSession($date);
        $b = ClassSession::factory()->create([
            'class_id' => $otherClass->id,
            'date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $first = $this->fetch(['per_page' => 1])->assertOk();

        $this->assertCount(1, $first->json('data'));
        $this->assertTrue($first->json('meta.has_more'));
        $this->assertNotNull($first->json('meta.next_cursor'));

        $second = $this->fetch(['per_page' => 1, 'cursor' => $first->json('meta.next_cursor')])->assertOk();

        // Asserting the second page is non-empty is the point. Without the id tiebreaker the
        // cursor is built from (date, start_time) alone, both rows compare equal, and page two
        // comes back empty — so a bare "the ids differ" check would pass against null.
        $this->assertCount(1, $second->json('data'), 'The second page must not be empty.');

        $seen = [$first->json('data.0.id'), $second->json('data.0.id')];
        sort($seen);

        $expected = [$a->id, $b->id];
        sort($expected);

        $this->assertSame($expected, $seen, 'Both rows must be served exactly once across the pages.');
    }
}
