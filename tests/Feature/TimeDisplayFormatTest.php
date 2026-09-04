<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClassSessionStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Locked display contract: every user-facing time uses the 12-hour `g:i A`
 * format. The boundary cases that are easy to get wrong — midnight and noon —
 * are pinned here so a regression in the formatting is caught immediately.
 *
 * @see docs/historical-backfill/plan/phase-3-session-picker-endpoint.md
 */
final class TimeDisplayFormatTest extends TestCase
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

    private function pastSession(string $date, string $start): ClassSession
    {
        return ClassSession::factory()->create([
            'class_id' => $this->class()->id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => '10:00:00',
            'status' => ClassSessionStatusEnum::SCHEDULED->value,
            'total_spots' => 12,
        ]);
    }

    #[Test]
    public function session_times_render_in_12_hour_format(): void
    {
        $date = now()->subDays(30)->toDateString();

        $cases = [
            '12:00:00' => '12:00 PM',
            '00:00:00' => '12:00 AM',
            '09:30:00' => '9:30 AM',
            '14:30:00' => '2:30 PM',
        ];

        $expected = [];

        foreach ($cases as $dbTime => $displayTime) {
            $session = $this->pastSession($date, $dbTime);
            $expected[$session->id] = $displayTime;
        }

        $rows = $this->actingAs($this->admin)
            ->getJson(self::URI . '?' . http_build_query([
                'package_id' => $this->package->id,
                'purchased_at' => now()->subDays(60)->toDateString(),
            ]))
            ->assertOk()
            ->json('data');

        foreach ($rows as $row) {
            $this->assertSame(
                $expected[$row['id']],
                $row['start_time'],
                "Session {$row['id']} must render " . $expected[$row['id']] . ' on screen.',
            );
        }
    }

    #[Test]
    public function midnight_and_noon_keep_their_am_pm_labels(): void
    {
        $date = now()->subDays(30)->toDateString();

        $midnight = $this->pastSession($date, '00:00:00');
        $noon = $this->pastSession($date, '12:00:00');

        $rows = collect($this->actingAs($this->admin)
            ->getJson(self::URI . '?' . http_build_query([
                'package_id' => $this->package->id,
                'purchased_at' => now()->subDays(60)->toDateString(),
            ]))
            ->assertOk()
            ->json('data'))
            ->keyBy('id');

        $this->assertSame('12:00 AM', $rows[$midnight->id]['start_time']);
        $this->assertSame('12:00 PM', $rows[$noon->id]['start_time']);
    }
}
