<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRoleEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Category + instructor filters in the scheduler and the operations class list.
 */
final class ClassFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ClassCategory $catA;

    private ClassCategory $catB;

    private Instructor $ann;

    private Instructor $bob;

    private Classes $classAnnA;

    private Classes $classBobB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);
        $this->catA = ClassCategory::factory()->create();
        $this->catB = ClassCategory::factory()->create();
        $this->ann = Instructor::factory()->create();
        $this->bob = Instructor::factory()->create();

        $this->classAnnA = $this->makeClass($this->ann, $this->catA, '09:00:00');
        $this->classBobB = $this->makeClass($this->bob, $this->catB, '12:00:00');
    }

    private function makeClass(Instructor $instructor, ClassCategory $category, string $start): Classes
    {
        $class = Classes::withoutEvents(fn () => Classes::factory()
            ->onWeekdays([WeekdayEnum::SUNDAY])
            ->create([
                'instructor_id' => $instructor->id,
                'class_category_id' => $category->id,
            ]));

        ClassSession::factory()->create([
            'class_id' => $class->id,
            'date' => '2026-11-10',
            'start_time' => $start,
            'end_time' => date('H:i:s', strtotime($start) + 3600),
            'status' => 'scheduled',
        ]);

        return $class;
    }

    private function sessions(string $query = ''): array
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/scheduler/sessions?date=2026-11-10' . $query)
            ->assertOk();

        return collect($response->json('data'))->pluck('id')->all();
    }

    #[Test]
    public function the_scheduler_lists_everything_without_filters(): void
    {
        $this->assertCount(2, $this->sessions());
    }

    #[Test]
    public function the_scheduler_filters_by_category(): void
    {
        $ids = $this->sessions('&category_id=' . $this->catA->id);

        $this->assertCount(1, $ids);
        $this->assertSame($this->classAnnA->id, ClassSession::find($ids[0])->class_id);
    }

    #[Test]
    public function category_and_instructor_filters_combine_with_and(): void
    {
        $this->assertCount(0, $this->sessions("&category_id={$this->catA->id}&instructor_id={$this->bob->id}"));
        $this->assertCount(1, $this->sessions("&category_id={$this->catB->id}&instructor_id={$this->bob->id}"));
    }

    #[Test]
    public function an_unknown_category_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/scheduler/sessions?date=2026-11-10&category_id=999999')
            ->assertUnprocessable();
    }

    #[Test]
    public function the_scheduler_exposes_a_category_list(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/scheduler/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function the_class_list_filters_by_category_and_instructor(): void
    {
        $byCategory = $this->actingAs($this->admin)
            ->getJson('/admin/operations/classes?category_id=' . $this->catA->id)
            ->assertOk();
        $this->assertSame([$this->classAnnA->id], collect($byCategory->json('data'))->pluck('id')->all());

        $both = $this->actingAs($this->admin)
            ->getJson("/admin/operations/classes?category_id={$this->catA->id}&instructor_id={$this->bob->id}")
            ->assertOk();
        $this->assertSame([], $both->json('data'));
    }

    #[Test]
    public function the_class_options_list_instructors_and_categories(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/operations/classes/options')
            ->assertOk()
            ->assertJsonCount(2, 'data.instructors')
            ->assertJsonCount(2, 'data.categories');
    }
}
