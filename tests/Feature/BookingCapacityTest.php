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
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\BookingSession\BookingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Capacity accounting and reservation locking.
 */
final class BookingCapacityTest extends TestCase
{
    use RefreshDatabase;

    private ClassSessionEloquentRepository $repository;

    private BookingSessionService $service;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ClassSessionEloquentRepository::class);
        $this->service = app(BookingSessionService::class);
        $this->package = Package::factory()->create(['total_credits' => 8]);
    }

    private function classSession(int $totalSpots = 2, ?string $date = null): ClassSession
    {
        $class = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create()->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
                'total_spots' => $totalSpots,
            ]));

        return ClassSession::factory()->create([
            'class_id' => $class->id,
            'date' => $date ?? now()->addWeek()->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'total_spots' => $totalSpots,
        ]);
    }

    private function activeBooking(int $credits = 4): Booking
    {
        // A fresh user each time: bookings.active_user_id enforces one active
        // booking per user.
        return Booking::factory()->create([
            'user_id' => User::factory()->create()->id,
            'package_id' => $this->package->id,
            'total_credits' => $credits,
            'remaining_credits' => $credits,
            'status' => BookingStatusEnum::ACTIVE->value,
            'expires_at' => now()->addYear(),
        ]);
    }

    // -------------------------------------------------------- capacity counting

    #[Test]
    public function a_cancelled_reservation_releases_its_spot(): void
    {
        $session = $this->classSession(totalSpots: 2);

        BookingSession::factory()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);

        $this->assertSame(1, $this->repository->getAvailableSpots($session->id));

        BookingSession::factory()->cancelled()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);

        // The cancelled row must not hold a seat: still one spot left, not zero.
        $this->assertSame(1, $this->repository->getAvailableSpots($session->id));
    }

    #[Test]
    public function the_repository_and_the_model_accessor_agree(): void
    {
        $session = $this->classSession(totalSpots: 3);

        BookingSession::factory()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);
        BookingSession::factory()->cancelled()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);

        $this->assertSame(
            $session->fresh()->available_spots,
            $this->repository->getAvailableSpots($session->id),
        );
    }

    #[Test]
    public function a_zero_capacity_session_has_no_spots(): void
    {
        // Previously returned PHP_INT_MAX, making it infinitely bookable.
        $session = $this->classSession(totalSpots: 1);
        $session->update(['total_spots' => 0]);

        $this->assertSame(0, $this->repository->getAvailableSpots($session->id));
    }

    #[Test]
    public function a_missing_session_has_no_spots(): void
    {
        $this->assertSame(0, $this->repository->getAvailableSpots(987654));
    }

    #[Test]
    public function a_full_session_reports_no_spots(): void
    {
        $session = $this->classSession(totalSpots: 1);

        BookingSession::factory()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);

        $this->assertSame(0, $this->repository->getAvailableSpots($session->id));
    }

    #[Test]
    public function the_full_session_metric_ignores_cancelled_reservations(): void
    {
        // Must be a future date: countUpcomingFullSessions() filters on
        // upcoming sessions, so a past fixture would pass vacuously.
        $session = $this->classSession(totalSpots: 1, date: now()->addWeek()->toDateString());

        BookingSession::factory()->cancelled()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);

        $this->assertSame(0, $this->repository->countUpcomingFullSessions());

        // A live reservation on the same session does fill it.
        BookingSession::factory()->create([
            'booking_id' => $this->activeBooking()->id,
            'class_session_id' => $session->id,
        ]);

        $this->assertSame(1, $this->repository->countUpcomingFullSessions());
    }

    // ------------------------------------------------------------- reservation

    #[Test]
    public function reserving_consumes_a_spot_and_a_credit(): void
    {
        $session = $this->classSession(totalSpots: 2);
        $booking = $this->activeBooking(credits: 4);

        $this->service->reserve($booking->id, $session->id);

        $this->assertSame(1, $this->repository->getAvailableSpots($session->id));
        $this->assertSame(3, $booking->fresh()->remaining_credits);
    }

    #[Test]
    public function reserving_a_full_session_is_rejected(): void
    {
        $session = $this->classSession(totalSpots: 1);

        $this->service->reserve($this->activeBooking()->id, $session->id);

        $this->expectException(ValidationException::class);

        $this->service->reserve($this->activeBooking()->id, $session->id);
    }

    #[Test]
    public function a_cancelled_reservation_frees_the_spot_for_someone_else(): void
    {
        $session = $this->classSession(totalSpots: 1);

        $first = $this->service->reserve($this->activeBooking()->id, $session->id);
        $first->update([
            'status' => BookingSessionStatusEnum::CANCELLED->value,
            'cancelled_at' => now(),
        ]);

        // Before the fix this threw: the cancelled row still counted as full.
        $second = $this->service->reserve($this->activeBooking()->id, $session->id);

        $this->assertSame(
            BookingSessionStatusEnum::RESERVED->value,
            $second->fresh()->status->value
        );
    }

    #[Test]
    public function no_reservation_can_push_a_session_over_capacity(): void
    {
        $session = $this->classSession(totalSpots: 3);

        for ($i = 0; $i < 3; $i++) {
            $this->service->reserve($this->activeBooking()->id, $session->id);
        }

        try {
            $this->service->reserve($this->activeBooking()->id, $session->id);
            $this->fail('Expected the fourth reservation to be rejected.');
        } catch (ValidationException) {
            // expected
        }

        $reserved = BookingSession::where('class_session_id', $session->id)
            ->where('status', BookingSessionStatusEnum::RESERVED->value)
            ->count();

        $this->assertSame(3, $reserved);
        $this->assertSame(0, $this->repository->getAvailableSpots($session->id));
    }

    // --------------------------------------------------------------- lock order

    #[Test]
    public function the_class_session_is_locked_before_its_capacity_is_counted(): void
    {
        // The race this guards: with the capacity check ahead of the lock, two
        // concurrent requests for the last spot both pass, then serialise on the
        // lock, and both insert. Asserting query order is the only way to catch
        // a regression here from a single-process test.
        $session = $this->classSession(totalSpots: 2);
        $booking = $this->activeBooking();

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->service->reserve($booking->id, $session->id);

        $lockIndex = null;
        $countIndex = null;

        foreach ($queries as $i => $sql) {
            $normalised = strtolower($sql);

            if ($lockIndex === null
                && str_contains($normalised, 'class_sessions')
                && str_contains($normalised, 'for update')) {
                $lockIndex = $i;
            }

            if ($countIndex === null
                && str_contains($normalised, 'count(*)')
                && str_contains($normalised, 'booking_sessions')) {
                $countIndex = $i;
            }
        }

        $this->assertNotNull($lockIndex, 'No SELECT ... FOR UPDATE on class_sessions was issued.');
        $this->assertNotNull($countIndex, 'No reservation count query was issued.');
        $this->assertLessThan(
            $countIndex,
            $lockIndex,
            'The class session must be locked before its reservations are counted.'
        );
    }

    #[Test]
    public function the_booking_is_also_locked_within_the_transaction(): void
    {
        $session = $this->classSession();
        $booking = $this->activeBooking();

        $locked = [];
        DB::listen(function ($query) use (&$locked) {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'for update')) {
                $locked[] = $sql;
            }
        });

        $this->service->reserve($booking->id, $session->id);

        $this->assertNotEmpty($locked);
        // Class session first, then booking — the same order oneTimeAttend uses.
        $this->assertStringContainsString('class_sessions', $locked[0]);
        $this->assertTrue(
            collect($locked)->contains(fn (string $sql) => str_contains($sql, 'bookings')),
            'The booking row was never locked.'
        );
    }
}
