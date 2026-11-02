<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\WeekdayEnum;
use App\Http\Resources\Api\V1\BookingSessionResource;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use App\Services\BookingSession\BookingSessionService;
use App\Support\Time\StudioTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sara's final refinements: cancelled is not missed, late cancel, add/swap, single-session packages, category.
 *
 * "Now" is pinned to 2026-11-02 12:00 in Asia/Damascus (09:00 UTC).
 */
final class BookingSessionRefinementsTest extends TestCase
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
    public function a_cancelled_reservation_is_never_counted_as_missed(): void
    {
        $future = $this->reservation($this->classSession('2026-11-09'));

        $this->service->applyBatch($future->booking_id, [
            ['booking_session_id' => $future->id, 'status' => 'cancelled'],
        ], $this->admin());

        $row = $future->fresh();
        $this->assertSame(BookingSessionStatusEnum::CANCELLED, $row->status);
        $this->assertFalse($row->isMissed());
        $this->assertSame(4, $row->booking->fresh()->remaining_credits);
        $this->assertSame(0, BookingSession::query()
            ->where('status', BookingSessionStatusEnum::RESERVED->value)
            ->where('attendance_status', AttendanceStatusEnum::MISSED->value)
            ->count());
    }

    #[Test]
    public function a_late_cancellation_is_missed_and_keeps_the_credit_spent(): void
    {
        // Starts in 6 hours: inside the 24h window.
        $soon = $this->reservation($this->classSession('2026-11-02', '18:00:00', '19:00:00'));

        $this->service->applyBatch($soon->booking_id, [
            ['booking_session_id' => $soon->id, 'status' => 'late_cancelled'],
        ], $this->admin());

        $row = $soon->fresh();
        $this->assertSame(BookingSessionStatusEnum::RESERVED, $row->status);
        $this->assertTrue($row->isMissed());
        $this->assertSame(3, $row->booking->fresh()->remaining_credits);
    }

    #[Test]
    public function a_late_cancel_is_refused_outside_the_window(): void
    {
        $far = $this->reservation($this->classSession('2026-11-20'));

        $this->expectException(ValidationException::class);

        $this->service->applyBatch($far->booking_id, [
            ['booking_session_id' => $far->id, 'status' => 'late_cancelled'],
        ], $this->admin());
    }

    #[Test]
    public function a_single_credit_package_cancelled_early_gets_its_credit_back_even_when_expired(): void
    {
        $future = $this->reservation($this->classSession('2026-11-09'), [
            'total_credits' => 1,
            'remaining_credits' => 0,
            'status' => BookingStatusEnum::EXHAUSTED->value,
            'expires_at' => now()->subHour(),
        ]);

        $this->service->applyBatch($future->booking_id, [
            ['booking_session_id' => $future->id, 'status' => 'cancelled'],
        ], $this->admin());

        $this->assertSame(1, $future->booking->fresh()->remaining_credits);
        $this->assertFalse($future->fresh()->isMissed());
    }

    #[Test]
    public function validity_runs_to_the_end_of_the_studio_day(): void
    {
        $until = StudioTime::validUntil(1);

        $this->assertSame('2026-11-03 23:59:59', $until->copy()->setTimezone('Asia/Damascus')->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function a_forgotten_class_can_be_added_and_uses_one_credit(): void
    {
        $existing = $this->reservation($this->classSession('2026-11-09'));
        $newFuture = $this->classSession('2026-11-12');
        $newPast = $this->classSession('2026-10-30');

        $this->service->applyBatch($existing->booking_id, [
            ['class_session_id' => $newFuture->id, 'status' => 'reserved'],
            ['class_session_id' => $newPast->id, 'status' => 'attended'],
        ], $this->admin());

        $booking = $existing->booking->fresh();
        $this->assertSame(1, $booking->remaining_credits);
        $this->assertSame(3, $booking->bookingSessions()->count());
        $this->assertSame(
            AttendanceStatusEnum::ATTENDED,
            BookingSession::query()->where('class_session_id', $newPast->id)->firstOrFail()->attendance_status,
        );
    }

    #[Test]
    public function adding_beyond_the_remaining_credits_rolls_everything_back(): void
    {
        $existing = $this->reservation($this->classSession('2026-11-09'), ['remaining_credits' => 1]);
        $a = $this->classSession('2026-11-12');
        $b = $this->classSession('2026-11-13');

        try {
            $this->service->applyBatch($existing->booking_id, [
                ['class_session_id' => $a->id, 'status' => 'reserved'],
                ['class_session_id' => $b->id, 'status' => 'reserved'],
            ], $this->admin());
            $this->fail('Expected a validation error.');
        } catch (ValidationException) {
            $this->assertSame(1, $existing->booking->fresh()->remaining_credits);
            $this->assertSame(1, $existing->booking->bookingSessions()->count());
        }
    }

    #[Test]
    public function a_started_class_cannot_be_reserved_and_a_future_one_cannot_be_attended(): void
    {
        $existing = $this->reservation($this->classSession('2026-11-09'));
        $started = $this->classSession('2026-11-02', '10:00:00', '11:00:00');
        $future = $this->classSession('2026-11-12');

        foreach ([[$started, 'reserved'], [$future, 'attended']] as [$session, $status]) {
            try {
                $this->service->applyBatch($existing->booking_id, [
                    ['class_session_id' => $session->id, 'status' => $status],
                ], $this->admin());
                $this->fail('Expected a validation error.');
            } catch (ValidationException) {
                $this->assertSame(3, $existing->booking->fresh()->remaining_credits);
            }
        }
    }

    #[Test]
    public function a_swap_cancels_the_old_class_and_books_the_new_one_atomically(): void
    {
        $old = $this->reservation($this->classSession('2026-11-09'));
        $new = $this->classSession('2026-11-12');

        $this->service->applyBatch($old->booking_id, [
            ['booking_session_id' => $old->id, 'status' => 'swap', 'new_class_session_id' => $new->id],
        ], $this->admin());

        $this->assertSame(BookingSessionStatusEnum::CANCELLED, $old->fresh()->status);
        $this->assertSame(3, $old->booking->fresh()->remaining_credits);
        $this->assertTrue(BookingSession::query()
            ->where('class_session_id', $new->id)
            ->where('status', BookingSessionStatusEnum::RESERVED->value)
            ->exists());
    }

    #[Test]
    public function the_booking_sessions_endpoint_exposes_the_class_category(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-09'));

        $this->actingAs($this->admin())
            ->getJson("/admin/operations/bookings/{$reservation->booking_id}/sessions")
            ->assertOk()
            ->assertJsonPath('data.0.category_name', fn ($name) => is_string($name) && $name !== '');
    }

    #[Test]
    public function the_mobile_api_returns_the_class_category_for_booking_sessions(): void
    {
        $reservation = $this->reservation($this->classSession('2026-11-09'));
        $user = $this->ownerOf($reservation);

        $page = app(BookingSessionEloquentRepository::class)
            ->listUserSessions($user->id);

        $payload = (new BookingSessionResource($page->items()[0]))
            ->toArray(request());

        $this->assertNotNull($payload['class_session']->resource->class->category);
        $this->assertIsInt($payload['class_session']->toArray(request())['class']->toArray(request())['category']->toArray(request())['id']);
    }
}
