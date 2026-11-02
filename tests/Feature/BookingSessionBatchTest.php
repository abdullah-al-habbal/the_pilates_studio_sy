<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
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
use App\Services\BookingSession\BookingSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Batch attendance / cancellation from the client profile ("View Classes").
 *
 * "Now" is pinned to 2026-11-02 12:00 in Asia/Damascus (09:00 UTC).
 */
final class BookingSessionBatchTest extends TestCase
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
            'attendance_status' => AttendanceStatusEnum::MISSED->value,
            'attended_at' => null,
        ]);
    }

    private function ownerOf(BookingSession $bookingSession): User
    {
        return $bookingSession->booking->user;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRoleEnum::ADMIN]);
    }

    private function sibling(BookingSession $bs, ClassSession $session): BookingSession
    {
        return BookingSession::factory()->create([
            'booking_id' => $bs->booking_id,
            'class_session_id' => $session->id,
            'status' => BookingSessionStatusEnum::RESERVED->value,
            'attendance_status' => AttendanceStatusEnum::MISSED->value,
            'attended_at' => null,
        ]);
    }

    #[Test]
    public function a_mixed_batch_applies_every_item(): void
    {
        $past = $this->reservation($this->classSession('2026-10-30'));
        $past2 = $this->sibling($past, $this->classSession('2026-10-31'));
        $future = $this->sibling($past, $this->classSession('2026-11-08'));

        $this->service->applyBatch($past->booking_id, [
            ['booking_session_id' => $past->id, 'status' => 'attended'],
            ['booking_session_id' => $past2->id, 'status' => 'missed'],
            ['booking_session_id' => $future->id, 'status' => 'cancelled'],
        ], $this->admin());

        $this->assertSame(AttendanceStatusEnum::ATTENDED, $past->fresh()->attendance_status);
        $this->assertSame(AttendanceStatusEnum::MISSED, $past2->fresh()->attendance_status);
        $this->assertSame(BookingSessionStatusEnum::CANCELLED, $future->fresh()->status);
        $this->assertSame(4, $past->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function one_invalid_item_rolls_the_whole_batch_back(): void
    {
        $past = $this->reservation($this->classSession('2026-10-30'));
        $future = $this->sibling($past, $this->classSession('2026-11-08'));
        $started = $this->sibling($past, $this->classSession('2026-11-02', '10:00:00', '11:00:00'));

        try {
            $this->service->applyBatch($past->booking_id, [
                ['booking_session_id' => $past->id, 'status' => 'attended'],
                ['booking_session_id' => $future->id, 'status' => 'cancelled'],
                ['booking_session_id' => $started->id, 'status' => 'cancelled'],
            ], $this->admin());
            $this->fail('Expected a validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey("items.{$started->id}", $e->errors());
        }

        $this->assertSame(AttendanceStatusEnum::MISSED, $past->fresh()->attendance_status);
        $this->assertSame(BookingSessionStatusEnum::RESERVED, $future->fresh()->status);
        $this->assertSame(3, $past->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function cancelling_inside_the_window_needs_the_override(): void
    {
        $inside = $this->reservation($this->classSession('2026-11-03', '11:30:00', '12:30:00'));

        try {
            $this->service->applyBatch($inside->booking_id, [
                ['booking_session_id' => $inside->id, 'status' => 'cancelled'],
            ], $this->admin());
            $this->fail('Expected a validation failure.');
        } catch (ValidationException) {
            $this->assertSame(BookingSessionStatusEnum::RESERVED, $inside->fresh()->status);
        }

        $this->service->applyBatch($inside->booking_id, [
            ['booking_session_id' => $inside->id, 'status' => 'cancelled', 'override' => true],
        ], $this->admin());

        $this->assertSame(1, (int) $inside->fresh()->cancellation_type);
        $this->assertSame(4, $inside->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function items_from_another_booking_are_not_found(): void
    {
        $mine = $this->reservation($this->classSession('2026-10-30'));
        $other = $this->reservation($this->classSession('2026-10-31'));

        $this->expectException(ModelNotFoundException::class);

        $this->service->applyBatch($mine->booking_id, [
            ['booking_session_id' => $other->id, 'status' => 'attended'],
        ], $this->admin());
    }

    #[Test]
    public function a_non_admin_cannot_apply_a_batch(): void
    {
        $reservation = $this->reservation($this->classSession('2026-10-30'));

        $this->expectException(ModelNotFoundException::class);

        $this->service->applyBatch($reservation->booking_id, [
            ['booking_session_id' => $reservation->id, 'status' => 'attended'],
        ], $this->ownerOf($reservation));
    }

    #[Test]
    public function attendance_cannot_be_set_on_a_cancelled_or_future_session(): void
    {
        $future = $this->reservation($this->classSession('2026-11-08'));

        $this->expectException(ValidationException::class);

        $this->service->applyBatch($future->booking_id, [
            ['booking_session_id' => $future->id, 'status' => 'attended'],
        ], $this->admin());
    }

    #[Test]
    public function the_batch_endpoint_is_admin_only_and_returns_json(): void
    {
        $reservation = $this->reservation($this->classSession('2026-10-30'));
        $url = "/admin/operations/bookings/{$reservation->booking_id}/sessions/batch";
        $payload = ['items' => [['booking_session_id' => $reservation->id, 'status' => 'attended']]];

        $this->actingAs($this->ownerOf($reservation))->postJson($url, $payload)->assertForbidden();
        $this->assertSame(AttendanceStatusEnum::MISSED, $reservation->fresh()->attendance_status);

        $this->actingAs($this->admin())->postJson($url, $payload)->assertOk();
        $this->assertSame(AttendanceStatusEnum::ATTENDED, $reservation->fresh()->attendance_status);

        $this->actingAs($this->admin())
            ->getJson("/admin/operations/bookings/{$reservation->booking_id}/sessions")
            ->assertOk()
            ->assertJsonPath('data.0.attendance_status', 'attended');
    }
}
