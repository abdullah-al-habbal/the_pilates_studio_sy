<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClassSessionStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Services\Validation\SessionConflictDetector;
use App\ValueObjects\Scheduling\ScheduleConflictVO;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SessionConflictDetectorTest extends TestCase
{
    use RefreshDatabase;

    private SessionConflictDetector $detector;

    private Instructor $instructor;

    private ClassCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(SessionConflictDetector::class);
        $this->instructor = Instructor::factory()->create();
        $this->category = ClassCategory::factory()->create();
    }

    /**
     * A class with one existing session, created without firing the observer so
     * the fixture is exactly what the test describes.
     */
    private function classWithSession(
        string $date,
        string $startTime,
        string $endTime,
        ?Instructor $instructor = null,
        string $status = ClassSessionStatusEnum::SCHEDULED->value,
        bool $trashed = false,
    ): Classes {
        $class = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => ($instructor ?? $this->instructor)->id,
                'class_category_id' => $this->category->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_date' => $date,
                'end_date' => Carbon::parse($date)->addMonth()->toDateString(),
            ]));

        $session = ClassSession::factory()->create([
            'class_id' => $class->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
        ]);

        if ($trashed) {
            $session->delete();
        }

        return $class;
    }

    /**
     * @return list<ScheduleConflictVO>
     */
    private function detect(string $date, string $start, string $end, ?int $instructorId = null, ?int $classId = null): array
    {
        return $this->detector->detect(
            dates: [Carbon::parse($date)->startOfDay()],
            startTime: $start,
            endTime: $end,
            instructorId: $instructorId ?? $this->instructor->id,
            classId: $classId,
        );
    }

    // ------------------------------------------------------- overlap semantics

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function overlapCases(): array
    {
        // Existing session is always 16:00-17:00 on 2026-08-02.
        return [
            'exact same interval' => ['16:00', '17:00', true],
            'partial overlap, later start' => ['16:30', '17:30', true],
            'partial overlap, earlier start' => ['15:30', '16:30', true],
            'new interval contained in existing' => ['16:15', '16:45', true],
            'existing interval contained in new' => ['15:00', '18:00', true],
            'back to back, new after existing' => ['17:00', '18:00', false],
            'back to back, new before existing' => ['15:00', '16:00', false],
            'clearly separate, later' => ['18:00', '19:00', false],
            'clearly separate, earlier' => ['09:00', '10:00', false],
        ];
    }

    #[Test]
    #[DataProvider('overlapCases')]
    public function it_applies_a_half_open_overlap_window(string $start, string $end, bool $expectConflict): void
    {
        $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');

        $conflicts = $this->detect('2026-08-02', $start, $end);

        $this->assertCount(
            $expectConflict ? 1 : 0,
            $conflicts,
            "{$start}-{$end} against 16:00-17:00"
        );
    }

    #[Test]
    public function a_different_date_never_conflicts(): void
    {
        $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');

        $this->assertSame([], $this->detect('2026-08-03', '16:00', '17:00'));
    }

    // ------------------------------------------------------------------- scope

    #[Test]
    public function it_reports_an_instructor_double_booking(): void
    {
        // The scenario from the requirement: same instructor, overlapping window.
        $existing = $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');

        $conflicts = $this->detect('2026-08-02', '16:30', '17:30');

        $this->assertCount(1, $conflicts);
        $this->assertSame(ScheduleConflictVO::REASON_INSTRUCTOR, $conflicts[0]->reason);
        $this->assertSame($existing->id, $conflicts[0]->classId);
    }

    #[Test]
    public function a_different_instructor_may_overlap(): void
    {
        // Agreed scope: only instructor collisions and self-overlap count. Two
        // classes with different instructors are free to run at the same time.
        $otherInstructor = Instructor::factory()->create();
        $this->classWithSession('2026-08-02', '16:00:00', '17:00:00', $otherInstructor);

        $this->assertSame([], $this->detect('2026-08-02', '16:30', '17:30'));
    }

    #[Test]
    public function it_reports_a_class_overlapping_itself(): void
    {
        $class = $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');

        $conflicts = $this->detector->detect(
            dates: [Carbon::parse('2026-08-02')->startOfDay()],
            startTime: '16:30',
            endTime: '17:30',
            instructorId: null,
            classId: $class->id,
        );

        $this->assertCount(1, $conflicts);
        $this->assertSame(ScheduleConflictVO::REASON_SAME_CLASS, $conflicts[0]->reason);
    }

    #[Test]
    public function nothing_conflicts_when_there_is_no_instructor_and_no_class(): void
    {
        $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');

        $this->assertSame([], $this->detector->detect(
            dates: [Carbon::parse('2026-08-02')->startOfDay()],
            startTime: '16:00',
            endTime: '17:00',
            instructorId: null,
            classId: null,
        ));
    }

    #[Test]
    public function the_ignored_session_is_excluded(): void
    {
        $class = $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');
        $sessionId = $class->sessions()->value('id');

        $conflicts = $this->detector->detect(
            dates: [Carbon::parse('2026-08-02')->startOfDay()],
            startTime: '16:00',
            endTime: '17:00',
            instructorId: $this->instructor->id,
            classId: $class->id,
            ignoreSessionId: (int) $sessionId,
        );

        $this->assertSame([], $conflicts);
    }

    // ------------------------------------------------------- excluded statuses

    #[Test]
    public function a_cancelled_session_releases_its_slot(): void
    {
        $this->classWithSession(
            '2026-08-02',
            '16:00:00',
            '17:00:00',
            status: ClassSessionStatusEnum::CANCELLED->value
        );

        $this->assertSame([], $this->detect('2026-08-02', '16:00', '17:00'));
    }

    #[Test]
    public function a_soft_deleted_session_is_ignored(): void
    {
        $this->classWithSession('2026-08-02', '16:00:00', '17:00:00', trashed: true);

        $this->assertSame([], $this->detect('2026-08-02', '16:00', '17:00'));
    }

    #[Test]
    public function a_completed_session_still_blocks(): void
    {
        $this->classWithSession(
            '2026-08-02',
            '16:00:00',
            '17:00:00',
            status: ClassSessionStatusEnum::COMPLETED->value
        );

        $this->assertCount(1, $this->detect('2026-08-02', '16:00', '17:00'));
    }

    #[Test]
    public function a_session_belonging_to_a_soft_deleted_class_is_ignored(): void
    {
        $class = $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');
        Classes::withoutEvents(fn () => $class->delete());

        $this->assertSame([], $this->detect('2026-08-02', '16:00', '17:00'));
    }

    // ------------------------------------------------------------- assert APIs

    #[Test]
    public function assert_no_conflicts_rejects_the_whole_batch_on_a_single_collision(): void
    {
        // One collision in the middle of an otherwise clean nine-week range.
        $this->classWithSession('2026-09-09', '16:00:00', '17:00:00');

        $dates = collect(['2026-08-02', '2026-08-05', '2026-09-09', '2026-09-30'])
            ->map(fn (string $date) => Carbon::parse($date)->startOfDay())
            ->all();

        try {
            $this->detector->assertNoConflicts(
                dates: $dates,
                startTime: '16:00',
                endTime: '17:00',
                instructorId: $this->instructor->id,
                classId: null,
            );

            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->implode(' ');

            $this->assertStringContainsString('2026-09-09', $message);
            $this->assertStringContainsString('16:00-17:00', $message);
        }
    }

    #[Test]
    public function assert_no_duplicates_rejects_a_repeated_date(): void
    {
        $class = $this->classWithSession('2026-08-02', '16:00:00', '17:00:00');

        $this->expectException(ValidationException::class);

        $this->detector->assertNoDuplicates(
            [Carbon::parse('2026-08-09'), Carbon::parse('2026-08-09')],
            '16:00',
            $class->id
        );
    }

    #[Test]
    public function assert_no_duplicates_catches_a_soft_deleted_row_holding_the_unique_index(): void
    {
        // The unique index on (class_id, date, start_time) does not honour
        // deleted_at, so a trashed row would otherwise surface as a raw
        // QueryException at insert time.
        $class = $this->classWithSession('2026-08-02', '16:00:00', '17:00:00', trashed: true);

        $this->expectException(ValidationException::class);

        $this->detector->assertNoDuplicates(
            [Carbon::parse('2026-08-02')],
            '16:00',
            $class->id
        );
    }
}
