<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\WeekdayEnum;
use App\Services\Classes\SessionDateCalculator;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pure date arithmetic — no database.
 *
 * Boots the framework only because the session-limit guard raises a
 * ValidationException, which needs the container to build a validator.
 */
final class SessionDateCalculatorTest extends TestCase
{
    private SessionDateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SessionDateCalculator;
    }

    /**
     * @param  list<Carbon>  $dates
     * @return list<string>
     */
    private function toStrings(array $dates): array
    {
        return array_map(fn (Carbon $date) => $date->toDateString(), $dates);
    }

    #[Test]
    public function it_generates_the_documented_sunday_and_wednesday_schedule(): void
    {
        $dates = $this->calculator->forWeekdays('2026-08-01', '2026-10-01', [
            WeekdayEnum::SUNDAY,
            WeekdayEnum::WEDNESDAY,
        ]);

        $this->assertSame([
            '2026-08-02', '2026-08-05', '2026-08-09', '2026-08-12',
            '2026-08-16', '2026-08-19', '2026-08-23', '2026-08-26',
            '2026-08-30', '2026-09-02', '2026-09-06', '2026-09-09',
            '2026-09-13', '2026-09-16', '2026-09-20', '2026-09-23',
            '2026-09-27', '2026-09-30',
        ], $this->toStrings($dates));

        $this->assertCount(18, $dates);
    }

    #[Test]
    public function the_start_date_is_skipped_when_it_is_not_a_selected_weekday(): void
    {
        // 2026-08-01 is a Saturday.
        $this->assertSame('Saturday', Carbon::parse('2026-08-01')->format('l'));

        $dates = $this->toStrings($this->calculator->forWeekdays('2026-08-01', '2026-10-01', [
            WeekdayEnum::SUNDAY,
            WeekdayEnum::WEDNESDAY,
        ]));

        $this->assertNotContains('2026-08-01', $dates);
        $this->assertSame('2026-08-02', $dates[0]);
    }

    #[Test]
    public function the_end_date_is_inclusive_but_only_if_it_matches(): void
    {
        // 2026-10-01 is a Thursday, so it is excluded.
        $this->assertSame('Thursday', Carbon::parse('2026-10-01')->format('l'));

        $thursdays = $this->toStrings(
            $this->calculator->forWeekdays('2026-09-28', '2026-10-01', [WeekdayEnum::THURSDAY])
        );

        $this->assertSame(['2026-10-01'], $thursdays);
    }

    #[Test]
    public function a_single_day_range_yields_one_session_when_the_weekday_matches(): void
    {
        $dates = $this->calculator->forWeekdays('2026-08-02', '2026-08-02', [WeekdayEnum::SUNDAY]);

        $this->assertSame(['2026-08-02'], $this->toStrings($dates));
    }

    #[Test]
    public function a_single_day_range_yields_nothing_when_the_weekday_does_not_match(): void
    {
        $dates = $this->calculator->forWeekdays('2026-08-01', '2026-08-01', [WeekdayEnum::SUNDAY]);

        $this->assertSame([], $dates);
    }

    #[Test]
    public function it_rejects_an_end_date_before_the_start_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->forWeekdays('2026-10-01', '2026-08-01', [WeekdayEnum::SUNDAY]);
    }

    #[Test]
    public function it_rejects_an_empty_weekday_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->forWeekdays('2026-08-01', '2026-10-01', []);
    }

    #[Test]
    public function it_requires_both_bounds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->forWeekdays('2026-08-01', null, [WeekdayEnum::SUNDAY]);
    }

    #[Test]
    public function one_selected_weekday_produces_a_weekly_cadence(): void
    {
        $dates = $this->toStrings(
            $this->calculator->forWeekdays('2026-08-01', '2026-10-01', [WeekdayEnum::SUNDAY])
        );

        $this->assertSame([
            '2026-08-02', '2026-08-09', '2026-08-16', '2026-08-23',
            '2026-08-30', '2026-09-06', '2026-09-13', '2026-09-20',
            '2026-09-27',
        ], $dates);
    }

    #[Test]
    public function all_seven_weekdays_is_equivalent_to_every_day(): void
    {
        $dates = $this->calculator->forWeekdays('2026-08-01', '2026-08-31', WeekdayEnum::cases());

        $this->assertCount(31, $dates);
        $this->assertSame('2026-08-01', $dates[0]->toDateString());
        $this->assertSame('2026-08-31', $dates[30]->toDateString());
    }

    #[Test]
    public function duplicate_weekdays_do_not_produce_duplicate_dates(): void
    {
        $dates = $this->calculator->forWeekdays('2026-08-01', '2026-08-31', [
            WeekdayEnum::SUNDAY,
            WeekdayEnum::SUNDAY,
        ]);

        $strings = $this->toStrings($dates);

        $this->assertSame($strings, array_values(array_unique($strings)));
        $this->assertCount(5, $dates);
    }

    #[Test]
    public function dates_come_back_in_ascending_order_regardless_of_weekday_order(): void
    {
        $dates = $this->toStrings($this->calculator->forWeekdays('2026-08-01', '2026-08-31', [
            WeekdayEnum::WEDNESDAY,
            WeekdayEnum::SUNDAY,
        ]));

        $sorted = $dates;
        sort($sorted);

        $this->assertSame($sorted, $dates);
    }

    #[Test]
    public function it_refuses_to_generate_more_than_the_maximum(): void
    {
        $this->expectException(ValidationException::class);

        // Every day for three years is well past the 500-session ceiling.
        $this->calculator->forWeekdays('2026-01-01', '2028-12-31', WeekdayEnum::cases());
    }

    // ---------------------------------------------------------------- interval

    #[Test]
    public function interval_mode_still_strides_from_the_start_date(): void
    {
        $dates = $this->toStrings($this->calculator->forInterval('2026-08-01', '2026-10-01', 7));

        $this->assertSame([
            '2026-08-01', '2026-08-08', '2026-08-15', '2026-08-22',
            '2026-08-29', '2026-09-05', '2026-09-12', '2026-09-19',
            '2026-09-26',
        ], $dates);
    }

    #[Test]
    public function interval_mode_cannot_express_the_sunday_wednesday_requirement(): void
    {
        $expected = [
            '2026-08-02', '2026-08-05', '2026-08-09', '2026-08-12',
            '2026-08-16', '2026-08-19', '2026-08-23', '2026-08-26',
            '2026-08-30', '2026-09-02', '2026-09-06', '2026-09-09',
            '2026-09-13', '2026-09-16', '2026-09-20', '2026-09-23',
            '2026-09-27', '2026-09-30',
        ];

        foreach ([1, 7, 14, 30] as $interval) {
            $actual = $this->toStrings($this->calculator->forInterval('2026-08-01', '2026-10-01', $interval));

            $this->assertNotSame(
                $expected,
                $actual,
                "interval_days={$interval} unexpectedly reproduced the weekday schedule"
            );
        }
    }

    #[Test]
    #[DataProvider('badIntervals')]
    public function interval_mode_rejects_a_non_positive_interval(int $interval): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->forInterval('2026-08-01', '2026-10-01', $interval);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function badIntervals(): array
    {
        return [
            'zero' => [0],
            'negative' => [-7],
        ];
    }
}
