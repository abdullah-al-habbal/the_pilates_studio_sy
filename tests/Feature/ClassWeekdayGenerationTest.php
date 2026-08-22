<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClassSessionStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Generation through the real ClassesObserver path.
 */
final class ClassWeekdayGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Instructor $instructor;

    private ClassCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = Instructor::factory()->create();
        $this->category = ClassCategory::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createClass(array $attributes = []): Classes
    {
        return Classes::create(array_merge([
            'instructor_id' => $this->instructor->id,
            'class_category_id' => $this->category->id,
            'recurrence_pattern_id' => null,
            'weekdays' => [WeekdayEnum::SUNDAY->value, WeekdayEnum::WEDNESDAY->value],
            'title' => ['en' => 'Reformer Flow', 'ar' => 'Reformer Flow'],
            'about' => ['en' => 'About', 'ar' => 'About'],
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'start_date' => '2026-08-01',
            'end_date' => '2026-10-01',
            'total_spots' => 8,
        ], $attributes));
    }

    /**
     * @return list<string>
     */
    private function sessionDates(Classes $class): array
    {
        return $class->sessions()->orderBy('date')->pluck('date')
            ->map(fn ($date) => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date)
            ->all();
    }

    #[Test]
    public function it_generates_only_the_selected_weekdays_in_the_range(): void
    {
        $class = $this->createClass();

        $dates = $this->sessionDates($class);

        $this->assertCount(18, $dates);
        $this->assertSame('2026-08-02', $dates[0]);
        $this->assertSame('2026-09-30', $dates[17]);

        // 2026-08-01 is a Saturday and must not be generated.
        $this->assertNotContains('2026-08-01', $dates);

        // 2026-10-01 is a Thursday and falls outside the selected weekdays.
        $this->assertNotContains('2026-10-01', $dates);

        foreach ($dates as $date) {
            $this->assertContains(
                Carbon::parse($date)->format('l'),
                ['Sunday', 'Wednesday'],
                "{$date} is not a Sunday or Wednesday"
            );
        }
    }

    #[Test]
    public function generated_sessions_copy_the_class_times_and_capacity(): void
    {
        $class = $this->createClass();

        $session = $class->sessions()->first();

        $this->assertSame('16:00:00', $session->start_time);
        $this->assertSame('17:00:00', $session->end_time);
        $this->assertSame(8, $session->total_spots);
        $this->assertSame(ClassSessionStatusEnum::SCHEDULED, $session->status);
    }

    #[Test]
    public function generation_never_creates_bookings(): void
    {
        $class = $this->createClass();

        $this->assertSame(18, $class->sessions()->count());
        $this->assertSame(0, $class->bookingSessions()->count());
    }

    #[Test]
    public function interval_mode_still_works(): void
    {
        $weekly = RecurrencePattern::factory()->create([
            'name' => 'weekly',
            'label' => ['en' => 'Weekly'],
            'interval_days' => 7,
        ]);

        $class = $this->createClass([
            'weekdays' => null,
            'recurrence_pattern_id' => $weekly->id,
        ]);

        $dates = $this->sessionDates($class);

        $this->assertCount(9, $dates);
        $this->assertSame('2026-08-01', $dates[0]);
    }

    // ------------------------------------------------------------ mode rules

    #[Test]
    public function a_class_with_neither_mode_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->createClass(['weekdays' => null, 'recurrence_pattern_id' => null]);
    }

    #[Test]
    public function a_class_with_both_modes_is_rejected(): void
    {
        $weekly = RecurrencePattern::factory()->create([
            'name' => 'weekly',
            'label' => ['en' => 'Weekly'],
            'interval_days' => 7,
        ]);

        $this->expectException(ValidationException::class);

        $this->createClass(['recurrence_pattern_id' => $weekly->id]);
    }

    #[Test]
    public function an_end_time_before_the_start_time_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->createClass(['start_time' => '17:00:00', 'end_time' => '16:00:00']);
    }

    #[Test]
    public function an_end_time_equal_to_the_start_time_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->createClass(['start_time' => '16:00:00', 'end_time' => '16:00:00']);
    }

    #[Test]
    public function a_missing_end_date_is_rejected_instead_of_silently_truncating(): void
    {
        $this->expectException(ValidationException::class);

        $this->createClass(['end_date' => null]);
    }

    #[Test]
    public function an_end_date_before_the_start_date_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->createClass(['start_date' => '2026-10-01', 'end_date' => '2026-08-01']);
    }

    #[Test]
    public function a_range_containing_none_of_the_selected_weekdays_is_rejected(): void
    {
        // 2026-08-03 (Mon) to 2026-08-04 (Tue) contains no Sunday or Wednesday.
        $this->expectException(ValidationException::class);

        $this->createClass(['start_date' => '2026-08-03', 'end_date' => '2026-08-04']);
    }

    #[Test]
    public function a_single_day_range_on_a_matching_weekday_generates_one_session(): void
    {
        $class = $this->createClass([
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-02',
        ]);

        $this->assertSame(['2026-08-02'], $this->sessionDates($class));
    }

    // ------------------------------------------------------------- conflicts

    #[Test]
    public function one_conflicting_occurrence_rejects_the_whole_batch(): void
    {
        // An existing class for the same instructor, overlapping on 2026-09-09.
        $existing = $this->createClass([
            'weekdays' => [WeekdayEnum::WEDNESDAY->value],
            'start_date' => '2026-09-09',
            'end_date' => '2026-09-09',
        ]);

        $this->assertSame(1, $existing->sessions()->count());

        $sessionsBefore = ClassSession::count();

        try {
            $this->createClass(['start_time' => '16:30:00', 'end_time' => '17:30:00']);
            $this->fail('Expected a ValidationException for the instructor conflict.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                '2026-09-09',
                collect($exception->errors())->flatten()->implode(' ')
            );
        }

        // Nothing partial: not one of the 18 candidate rows was written.
        $this->assertSame($sessionsBefore, ClassSession::count());
    }

    #[Test]
    public function a_different_instructor_may_run_an_overlapping_class(): void
    {
        $this->createClass();

        $other = Instructor::factory()->create();

        $second = $this->createClass([
            'instructor_id' => $other->id,
            'start_time' => '16:30:00',
            'end_time' => '17:30:00',
        ]);

        $this->assertSame(18, $second->sessions()->count());
    }

    #[Test]
    public function back_to_back_classes_for_the_same_instructor_are_allowed(): void
    {
        $this->createClass();

        $second = $this->createClass([
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
        ]);

        $this->assertSame(18, $second->sessions()->count());
    }
}
