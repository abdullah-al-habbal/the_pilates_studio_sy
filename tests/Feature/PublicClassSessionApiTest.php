<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClassSessionStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PublicClassSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private Classes $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->class = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => Instructor::factory()->create()->id,
                'class_category_id' => ClassCategory::factory()->create()->id,
            ]));
    }

    #[Test]
    public function an_exact_date_returns_scheduled_sessions_even_when_the_date_is_in_the_past(): void
    {
        $date = now()->subWeek()->toDateString();

        $scheduled = $this->classSession($date, ClassSessionStatusEnum::SCHEDULED);
        $this->classSession($date, ClassSessionStatusEnum::COMPLETED);
        $this->classSession($date, ClassSessionStatusEnum::CANCELLED);

        $response = $this->getJson("/api/v1/public/class-sessions?date={$date}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $scheduled->id)
            ->assertJsonPath('data.data.0.status', ClassSessionStatusEnum::SCHEDULED->value);
    }

    #[Test]
    public function requests_without_an_exact_date_only_return_upcoming_scheduled_sessions(): void
    {
        $this->classSession(now()->subDay()->toDateString(), ClassSessionStatusEnum::SCHEDULED);
        $upcoming = $this->classSession(now()->addDay()->toDateString(), ClassSessionStatusEnum::SCHEDULED);
        $this->classSession(now()->addDay()->toDateString(), ClassSessionStatusEnum::CANCELLED);

        $response = $this->getJson('/api/v1/public/class-sessions');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $upcoming->id)
            ->assertJsonPath('data.data.0.status', ClassSessionStatusEnum::SCHEDULED->value);
    }

    #[Test]
    public function date_after_can_start_a_range_in_the_past(): void
    {
        $excluded = $this->classSession(now()->subDays(10)->toDateString(), ClassSessionStatusEnum::SCHEDULED);
        $included = $this->classSession(now()->subDays(5)->toDateString(), ClassSessionStatusEnum::SCHEDULED);

        $dateAfter = now()->subDays(7)->toDateString();
        $response = $this->getJson("/api/v1/public/class-sessions?date_after={$dateAfter}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonMissing(['id' => $excluded->id]);
    }

    #[Test]
    public function date_before_can_end_a_range_in_the_past(): void
    {
        $included = $this->classSession(now()->subDays(10)->toDateString(), ClassSessionStatusEnum::SCHEDULED);
        $excluded = $this->classSession(now()->subDays(5)->toDateString(), ClassSessionStatusEnum::SCHEDULED);

        $dateBefore = now()->subDays(7)->toDateString();
        $response = $this->getJson("/api/v1/public/class-sessions?date_before={$dateBefore}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $included->id)
            ->assertJsonMissing(['id' => $excluded->id]);
    }

    private function classSession(string $date, ClassSessionStatusEnum $status): ClassSession
    {
        return ClassSession::factory()->create([
            'class_id' => $this->class->id,
            'date' => $date,
            'status' => $status->value,
        ]);
    }
}
