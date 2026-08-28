<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\User;
use App\Repositories\Eloquent\BookingSession\BookingSessionEloquentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Attendance reporting is keyed on the class session's date, not on when the row was written.
 *
 * Historical backfill writes attendance long after the class ran, so `created_at` would pile
 * every backfilled session onto the day the admin typed it in.
 */
final class AttendanceBusinessDateTest extends TestCase
{
    use RefreshDatabase;

    private BookingSessionEloquentRepository $repository;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(BookingSessionEloquentRepository::class);

        $package = Package::factory()->create(['total_credits' => 20]);

        $this->booking = Booking::factory()->create([
            'user_id' => User::factory()->create()->id,
            'package_id' => $package->id,
            'total_credits' => 20,
            'remaining_credits' => 20,
        ]);
    }

    private function classSession(string $date): ClassSession
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
            'date' => $date,
        ]);
    }

    private function attendance(string $classDate, AttendanceStatusEnum $status): BookingSession
    {
        return BookingSession::factory()->create([
            'booking_id' => $this->booking->id,
            'class_session_id' => $this->classSession($classDate)->id,
            'status' => BookingSessionStatusEnum::RESERVED->value,
            'attendance_status' => $status->value,
        ]);
    }

    #[Test]
    public function the_attendance_trend_is_keyed_on_the_class_date_not_the_row_creation_date(): void
    {
        $classDate = now()->subDays(10)->toDateString();

        // Written today, as a backfill would be — but the class ran ten days ago.
        $this->attendance($classDate, AttendanceStatusEnum::ATTENDED);

        $trend = $this->repository->getAttendanceTrend(30);

        $this->assertArrayHasKey($classDate, $trend->all(), 'Attendance must land on the class date.');
        $this->assertSame(1, (int) $trend[$classDate]);
        $this->assertArrayNotHasKey(
            now()->toDateString(),
            $trend->all(),
            'Attendance must not pile onto the day the admin entered it.'
        );
    }

    #[Test]
    public function the_attendance_trend_excludes_sessions_older_than_the_window(): void
    {
        $this->attendance(now()->subDays(5)->toDateString(), AttendanceStatusEnum::ATTENDED);
        $this->attendance(now()->subDays(90)->toDateString(), AttendanceStatusEnum::ATTENDED);

        $trend = $this->repository->getAttendanceTrend(30);

        $this->assertCount(1, $trend);
        $this->assertArrayHasKey(now()->subDays(5)->toDateString(), $trend->all());
    }

    #[Test]
    public function the_attendance_trend_ignores_soft_deleted_class_sessions(): void
    {
        // The raw join bypasses the SoftDeletes global scope, so this guard has to be explicit.
        $classDate = now()->subDays(7)->toDateString();
        $session = $this->attendance($classDate, AttendanceStatusEnum::ATTENDED);

        // ClassSessionObserver blocks deleting a booked session, so bypass events to reach the
        // soft-deleted DB state this guard is about.
        ClassSession::withoutEvents(fn () => $session->classSession->delete());

        $this->assertCount(0, $this->repository->getAttendanceTrend(30));
    }

    #[Test]
    public function the_attendance_trend_counts_only_attended_sessions(): void
    {
        $classDate = now()->subDays(3)->toDateString();

        $this->attendance($classDate, AttendanceStatusEnum::ATTENDED);
        $this->attendance($classDate, AttendanceStatusEnum::MISSED);

        $this->assertSame(1, (int) $this->repository->getAttendanceTrend(30)[$classDate]);
    }

    #[Test]
    public function missed_sessions_are_counted_into_the_month_the_class_ran(): void
    {
        $classDate = now()->subMonths(2)->startOfMonth()->addDays(4);

        $this->attendance($classDate->toDateString(), AttendanceStatusEnum::MISSED);

        $this->assertSame(
            1,
            $this->repository->countMissedForMonth((int) $classDate->month, (int) $classDate->year),
            'A backfilled miss belongs to the month the class ran.'
        );

        $this->assertSame(
            0,
            $this->repository->countMissedForMonth((int) now()->month, (int) now()->year),
            'It must not be counted into the month it was entered.'
        );
    }
}
