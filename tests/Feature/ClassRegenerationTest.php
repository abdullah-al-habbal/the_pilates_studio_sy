<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WeekdayEnum;
use App\Models\BookingSession;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClassRegenerationTest extends TestCase
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
    public function changing_the_weekdays_regenerates_the_schedule(): void
    {
        $class = $this->createClass();
        $this->assertCount(18, $this->sessionDates($class));

        $class->update(['weekdays' => [WeekdayEnum::MONDAY->value]]);

        $dates = $this->sessionDates($class->fresh());

        $this->assertSame('2026-08-03', $dates[0]);

        foreach ($dates as $date) {
            $this->assertSame('Monday', Carbon::parse($date)->format('l'));
        }
    }

    #[Test]
    public function changing_the_date_range_regenerates_the_schedule(): void
    {
        $class = $this->createClass();

        $class->update(['end_date' => '2026-08-31']);

        $dates = $this->sessionDates($class->fresh());

        $this->assertSame('2026-08-02', $dates[0]);
        $this->assertSame('2026-08-30', end($dates));
        $this->assertCount(9, $dates);
    }

    #[Test]
    public function changing_only_the_time_keeps_the_dates_and_updates_the_times(): void
    {
        $class = $this->createClass();
        $before = $this->sessionDates($class);

        $class->update(['start_time' => '18:00:00', 'end_time' => '19:00:00']);

        $fresh = $class->fresh();

        $this->assertSame($before, $this->sessionDates($fresh));
        $this->assertSame('18:00:00', $fresh->sessions()->first()->start_time);
    }

    #[Test]
    public function switching_from_interval_to_weekday_mode_regenerates(): void
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

        $this->assertCount(9, $this->sessionDates($class));

        $class->update([
            'recurrence_pattern_id' => null,
            'weekdays' => [WeekdayEnum::SUNDAY->value, WeekdayEnum::WEDNESDAY->value],
        ]);

        $this->assertCount(18, $this->sessionDates($class->fresh()));
    }

    #[Test]
    public function regenerating_the_same_schedule_twice_yields_the_same_dates(): void
    {
        $class = $this->createClass();
        $first = $this->sessionDates($class);

        // Touch a schedule field and put it straight back.
        $class->update(['end_date' => '2026-09-30']);
        $class->update(['end_date' => '2026-10-01']);

        $this->assertSame($first, $this->sessionDates($class->fresh()));
    }

    #[Test]
    public function a_non_schedule_change_does_not_regenerate(): void
    {
        $class = $this->createClass();
        $ids = $class->sessions()->orderBy('date')->pluck('id')->all();

        $class->update(['total_spots' => 12]);

        // Session rows are untouched, so their ids survive.
        $this->assertSame($ids, $class->fresh()->sessions()->orderBy('date')->pluck('id')->all());
    }

    // -------------------------------------------------- booked-class protection

    #[Test]
    public function a_schedule_change_is_blocked_once_a_session_is_booked(): void
    {
        $class = $this->createClass();
        $this->bookFirstSession($class);

        $this->expectException(ValidationException::class);

        $class->update(['weekdays' => [WeekdayEnum::MONDAY->value]]);
    }

    #[Test]
    public function a_booked_class_keeps_its_sessions_after_a_blocked_change(): void
    {
        $class = $this->createClass();
        $this->bookFirstSession($class);

        $before = $this->sessionDates($class);

        try {
            $class->update(['start_date' => '2026-08-10']);
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame($before, $this->sessionDates($class->fresh()));
    }

    #[Test]
    public function deleting_a_booked_class_is_blocked(): void
    {
        $class = $this->createClass();
        $this->bookFirstSession($class);

        $this->expectException(ValidationException::class);

        $class->delete();
    }

    #[Test]
    public function an_unbooked_class_can_be_deleted_and_its_sessions_go_with_it(): void
    {
        $class = $this->createClass();

        $class->delete();

        $this->assertSame(0, $class->sessions()->withTrashed()->count());
    }

    private function bookFirstSession(Classes $class): void
    {
        BookingSession::factory()->create([
            'class_session_id' => $class->sessions()->first()->id,
        ]);
    }
}
