<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WeekdayEnum;
use App\Filament\Admin\Resources\Classes\Pages\CreateClasses;
use App\Filament\Admin\Resources\Classes\Pages\EditClasses;
use App\Filament\Admin\Resources\Classes\Pages\ListClasses;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end through the actual admin pages, so the form, table and
 * mode-normalisation hooks are exercised the way an admin exercises them.
 */
final class ClassesAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private Instructor $instructor;

    private ClassCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // The panel is normally resolved from the request path by Filament's
        // middleware; Livewire component tests render outside it, and the
        // provider does not mark the panel as default.
        Filament::setCurrentPanel('admin');

        $this->actingAs(User::factory()->admin()->create());

        $this->instructor = Instructor::factory()->create();
        $this->category = ClassCategory::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'instructor_id' => $this->instructor->id,
            'class_category_id' => $this->category->id,
            'title' => 'Reformer Flow',
            'about' => 'About this class',
            'schedule_mode' => 'weekdays',
            'weekdays' => [WeekdayEnum::SUNDAY->value, WeekdayEnum::WEDNESDAY->value],
            'start_date' => '2026-08-01',
            'end_date' => '2026-10-01',
            'start_time' => '16:00',
            'end_time' => '17:00',
            'total_spots' => 8,
            'status' => 'active',

            // The images Repeater seeds one empty row whose upload is required
            // on create; this feature has nothing to say about it.
            'images' => [],
        ], $overrides);
    }

    #[Test]
    public function the_class_list_page_renders(): void
    {
        Classes::withoutEvents(fn () => Classes::factory()->count(3)->create([
            'instructor_id' => $this->instructor->id,
            'class_category_id' => $this->category->id,
        ]));

        Livewire::test(ListClasses::class)
            ->assertSuccessful();
    }

    #[Test]
    public function an_admin_can_create_a_weekday_class_and_get_the_expected_sessions(): void
    {
        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $class = Classes::firstOrFail();

        $this->assertSame(['sunday', 'wednesday'], $class->weekdays);
        $this->assertNull($class->recurrence_pattern_id);
        $this->assertSame(18, $class->sessions()->count());
        $this->assertSame('2026-08-02', $class->sessions()->first()->date->format('Y-m-d'));
    }

    #[Test]
    public function the_create_form_rejects_a_weekday_class_with_no_weekdays_selected(): void
    {
        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData(['weekdays' => []]))
            ->call('create')
            ->assertHasFormErrors(['weekdays']);

        $this->assertSame(0, Classes::count());
    }

    #[Test]
    public function the_create_form_rejects_an_end_time_before_the_start_time(): void
    {
        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData(['start_time' => '17:00', 'end_time' => '16:00']))
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, Classes::count());
    }

    #[Test]
    public function editing_a_class_to_interval_mode_clears_its_weekdays(): void
    {
        $weekly = RecurrencePattern::factory()->create([
            'name' => 'weekly',
            'label' => ['en' => 'Weekly'],
            'interval_days' => 7,
        ]);

        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $class = Classes::firstOrFail();
        $this->assertSame(18, $class->sessions()->count());

        Livewire::test(EditClasses::class, ['record' => $class->getKey()])
            ->fillForm([
                'schedule_mode' => 'interval',
                'recurrence_pattern_id' => $weekly->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $class->refresh();

        $this->assertNull($class->weekdays);
        $this->assertSame($weekly->id, $class->recurrence_pattern_id);
        $this->assertSame(9, $class->sessions()->count());
    }

    #[Test]
    public function the_form_surfaces_an_instructor_conflict_instead_of_creating_the_class(): void
    {
        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Classes::count());

        // Same instructor, overlapping window, overlapping dates.
        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData(['start_time' => '16:30', 'end_time' => '17:30']))
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(1, Classes::count());
    }

    #[Test]
    public function an_admin_can_add_an_instructor_from_the_select_modal(): void
    {
        // The dev database can legitimately hold zero instructors while the
        // field is required, which otherwise deadlocks the whole create form.
        Instructor::query()->forceDelete();

        $component = Livewire::test(CreateClasses::class)
            ->callFormComponentAction('instructor_id', 'createOption', data: [
                'name' => ['en' => 'Sara', 'ar' => 'سارة'],
            ])
            ->assertHasNoFormErrors();

        $instructor = Instructor::firstOrFail();

        $this->assertSame('Sara', $instructor->getTranslation('name', 'en'));
        $this->assertSame('سارة', $instructor->getTranslation('name', 'ar'));

        // The new option must also be selected, or the admin has to hunt for it.
        $component->assertFormSet(['instructor_id' => $instructor->id]);
    }

    #[Test]
    public function an_instructor_created_from_the_modal_can_carry_a_class(): void
    {
        Instructor::query()->forceDelete();

        $component = Livewire::test(CreateClasses::class)
            ->callFormComponentAction('instructor_id', 'createOption', data: [
                'name' => ['en' => 'Sara', 'ar' => 'سارة'],
            ]);

        $component
            ->fillForm($this->formData(['instructor_id' => Instructor::firstOrFail()->id]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(18, Classes::firstOrFail()->sessions()->count());
    }
}
