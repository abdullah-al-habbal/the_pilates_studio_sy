<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\User;
use App\Services\BookingSession\BookingSessionService;
use App\Services\BookingSession\CancellationPolicyService;
use App\Support\Time\StudioTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cancellation policy (24h, studio timezone), ownership, admin override and credit restore.
 *
 * "Now" is pinned to 2026-11-02 12:00 in Asia/Damascus (09:00 UTC).
 */
final class BookingSessionCancellationTest extends TestCase
{
    use RefreshDatabase;

    private BookingSessionService $service;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-02 12:00:00', 'Asia/Damascus'));
        Carbon::setTestNow(Carbon::parse('2026-11-02 12:00:00', 'Asia/Damascus'));

        $this->service = app(BookingSessionService::class);
        $this->package = Package::factory()->create(['total_credits' => 4]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function classSession(string $date, string $start = '10:00:00', string $end = '11:00:00'): ClassSession
    {
        $class = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create()->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
                'total_spots' => 5,
            ]));

        return ClassSession::factory()->create([
            'class_id' => $class->id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'total_spots' => 5,
        ]);
    }

    private function reservation(ClassSession $session, array $booking = []): BookingSession
    {
        $booking = Booking::factory()->create([
            'user_id' => User::factory()->create()->id,
            'package_id' => $this->package->id,
            'total_credits' => 4,
            'remaining_credits' => 3,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->addMonth(),
            ...$booking,
        ]);

        return BookingSession::factory()->create([
            'booking_id' => $booking->id,
            'class_session_id' => $session->id,
            'status' => BookingSessionStatusEnum::RESERVED->value,
        ]);
    }

    private function ownerOf(BookingSession $bookingSession): User
    {
        return $bookingSession->booking->user;
    }

    #[Test]
    public function the_window_is_evaluated_in_the_studio_timezone(): void
    {
        // 2026-11-03 12:30 Damascus is 24h30m away: outside the window.
        // A UTC-naive comparison (now = 09:00) would see 27h30m and also allow it, so also
        // check the edge: 2026-11-03 11:30 Damascus is 23h30m away and must be blocked,
        // while the naive UTC read (26h30m) would wrongly allow it.
        $outside = $this->classSession('2026-11-03', '12:30:00', '13:30:00');
        $inside = $this->classSession('2026-11-03', '11:30:00', '12:30:00');

        $policy = app(CancellationPolicyService::class);

        $this->assertTrue($policy->canCancel($outside));
        $this->assertFalse($policy->canCancel($inside));
        $this->assertSame('Asia/Damascus', StudioTime::timezone());
    }

    #[Test]
    public function the_owner_can_cancel_outside_the_window_and_gets_the_credit_back(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'));

        $this->service->cancel($reservation->id, $this->ownerOf($reservation));

        $reservation->refresh();
        $this->assertSame(BookingSessionStatusEnum::CANCELLED, $reservation->status);
        $this->assertSame($this->ownerOf($reservation)->id, $reservation->cancelled_by_user_id);
        $this->assertSame(0, (int) $reservation->cancellation_type);
        $this->assertSame(4, $reservation->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function the_owner_cannot_cancel_inside_the_window(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-03', '11:30:00', '12:30:00'));

        $this->expectException(ValidationException::class);

        try {
            $this->service->cancel($reservation->id, $this->ownerOf($reservation));
        } finally {
            $this->assertSame(BookingSessionStatusEnum::RESERVED, $reservation->fresh()->status);
            $this->assertSame(3, $reservation->booking->fresh()->remaining_credits);
        }
    }

    #[Test]
    public function an_admin_override_bypasses_the_window_before_the_class_starts(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-03', '11:30:00', '12:30:00'));
        $admin = User::factory()->admin()->create();

        $this->service->cancel($reservation->id, $admin, adminOverride: true);

        $reservation->refresh();
        $this->assertSame(BookingSessionStatusEnum::CANCELLED, $reservation->status);
        $this->assertSame($admin->id, $reservation->cancelled_by_admin_id);
        $this->assertSame(1, (int) $reservation->cancellation_type);
        $this->assertSame(4, $reservation->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function a_customer_cannot_use_the_admin_override(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-03', '11:30:00', '12:30:00'));

        $this->expectException(ValidationException::class);

        $this->service->cancel($reservation->id, $this->ownerOf($reservation), adminOverride: true);
    }

    #[Test]
    public function nobody_can_cancel_once_the_class_has_started_not_even_an_admin(): void
    {
        // Started at 11:00 Damascus today; it is 12:00 now.
        $reservation = $this->reservation($this->classSession('2026-11-02', '11:00:00', '13:00:00'));
        $admin = User::factory()->admin()->create();

        try {
            $this->service->cancel($reservation->id, $admin, adminOverride: true);
            $this->fail('Expected the cancellation to be rejected.');
        } catch (ValidationException) {
            $this->assertSame(BookingSessionStatusEnum::RESERVED, $reservation->fresh()->status);
            $this->assertSame(3, $reservation->booking->fresh()->remaining_credits);
        }
    }

    #[Test]
    public function another_client_cannot_cancel_a_reservation_that_is_not_theirs(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'));
        $stranger = User::factory()->create();

        try {
            $this->service->cancel($reservation->id, $stranger);
            $this->fail('Expected a not-found response for a foreign reservation.');
        } catch (ModelNotFoundException) {
            $this->assertSame(BookingSessionStatusEnum::RESERVED, $reservation->fresh()->status);
            $this->assertSame(3, $reservation->booking->fresh()->remaining_credits);
        }
    }

    #[Test]
    public function cancelling_twice_restores_the_credit_only_once(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'));
        $owner = $this->ownerOf($reservation);

        $this->service->cancel($reservation->id, $owner);

        try {
            $this->service->cancel($reservation->id, $owner);
            $this->fail('Expected the second cancellation to be rejected.');
        } catch (ValidationException) {
            $this->assertSame(4, $reservation->booking->fresh()->remaining_credits);
        }
    }

    #[Test]
    public function an_exhausted_booking_is_reactivated_when_still_valid(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'), [
            'remaining_credits' => 0,
            'status' => BookingStatusEnum::EXHAUSTED->value,
        ]);

        $this->service->cancel($reservation->id, $this->ownerOf($reservation));

        $booking = $reservation->booking->fresh();
        $this->assertSame(1, $booking->remaining_credits);
        $this->assertSame(BookingStatusEnum::ACTIVE, $booking->status);
    }

    #[Test]
    public function an_exhausted_booking_stays_exhausted_when_expired(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'), [
            'remaining_credits' => 0,
            'status' => BookingStatusEnum::EXHAUSTED->value,
            'expires_at' => now()->subDay(),
        ]);

        $this->service->cancel($reservation->id, $this->ownerOf($reservation));

        $this->assertSame(BookingStatusEnum::EXHAUSTED, $reservation->booking->fresh()->status);
    }

    #[Test]
    public function an_exhausted_booking_is_not_reactivated_when_the_client_has_another_active_one(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'), [
            'remaining_credits' => 0,
            'status' => BookingStatusEnum::EXHAUSTED->value,
        ]);

        Booking::factory()->create([
            'user_id' => $reservation->booking->user_id,
            'package_id' => $this->package->id,
            'total_credits' => 4,
            'remaining_credits' => 4,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->addMonth(),
        ]);

        $this->service->cancel($reservation->id, $this->ownerOf($reservation));

        $this->assertSame(BookingStatusEnum::EXHAUSTED, $reservation->booking->fresh()->status);
    }

    #[Test]
    public function a_frozen_booking_keeps_its_status_but_gets_the_credit_back(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'), [
            'status' => BookingStatusEnum::FROZEN->value,
        ]);

        $this->service->cancel($reservation->id, $this->ownerOf($reservation));

        $booking = $reservation->booking->fresh();
        $this->assertSame(BookingStatusEnum::FROZEN, $booking->status);
        $this->assertSame(4, $booking->remaining_credits);
    }

    #[Test]
    public function the_credit_never_exceeds_the_package_total(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-05'), [
            'remaining_credits' => 4,
        ]);

        $this->service->cancel($reservation->id, $this->ownerOf($reservation));

        $this->assertSame(4, $reservation->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function a_client_can_rebook_straight_after_cancelling(): void
    {
        $session = $this->classSession('2026-11-05');
        $reservation = $this->reservation($session);
        $owner = $this->ownerOf($reservation);

        $this->service->cancel($reservation->id, $owner);
        $rebooked = $this->service->reserve($reservation->booking_id, $session->id);

        $this->assertSame(BookingSessionStatusEnum::RESERVED, $rebooked->status);
        $this->assertSame(3, $reservation->booking->fresh()->remaining_credits);
    }
}
