<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Admin\Resources\Classes\Schemas\ClassesForm;
use App\Models\Instructor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the form definition itself: a mistyped Filament method or a bad
 * chained call would otherwise only show up when an admin opens the page.
 */
final class ClassesFormSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_class_form_schema_builds_and_exposes_both_scheduling_modes(): void
    {
        $schema = ClassesForm::configure(Schema::make());

        $names = [];

        $collect = function (array $components) use (&$collect, &$names): void {
            foreach ($components as $component) {
                if (method_exists($component, 'getName')) {
                    $names[] = $component->getName();
                }

                if (method_exists($component, 'getDefaultChildComponents')) {
                    $collect($component->getDefaultChildComponents());
                }
            }
        };

        $collect($schema->getComponents());

        $this->assertContains('schedule_mode', $names);
        $this->assertContains('weekdays', $names);
        $this->assertContains('recurrence_pattern_id', $names);
        $this->assertContains('start_date', $names);
        $this->assertContains('end_date', $names);
        $this->assertContains('start_time', $names);
        $this->assertContains('end_time', $names);
    }

    // ------------------------------------------------- mode normalisation

    #[Test]
    public function selecting_weekdays_clears_the_recurrence_pattern(): void
    {
        $data = ClassesForm::normaliseScheduleMode([
            'schedule_mode' => ClassesForm::MODE_WEEKDAYS,
            'weekdays' => ['sunday', 'wednesday'],
            'recurrence_pattern_id' => 3,
        ]);

        $this->assertSame(['sunday', 'wednesday'], $data['weekdays']);
        $this->assertNull($data['recurrence_pattern_id']);
        $this->assertArrayNotHasKey('schedule_mode', $data);
    }

    #[Test]
    public function choosing_interval_mode_clears_a_stale_weekday_list(): void
    {
        // The weekday checkbox list is hidden in interval mode, so it never
        // reaches $data — the stale value must be nulled explicitly.
        $data = ClassesForm::normaliseScheduleMode([
            'schedule_mode' => ClassesForm::MODE_INTERVAL,
            'recurrence_pattern_id' => 3,
        ]);

        $this->assertNull($data['weekdays']);
        $this->assertSame(3, $data['recurrence_pattern_id']);
    }

    #[Test]
    public function an_empty_weekday_list_is_treated_as_interval_mode(): void
    {
        $data = ClassesForm::normaliseScheduleMode([
            'weekdays' => [],
            'recurrence_pattern_id' => 7,
        ]);

        $this->assertNull($data['weekdays']);
        $this->assertSame(7, $data['recurrence_pattern_id']);
    }

    #[Test]
    public function weekday_keys_are_reindexed(): void
    {
        // Filament's checkbox list can hand back a sparse array.
        $data = ClassesForm::normaliseScheduleMode([
            'weekdays' => [2 => 'wednesday', 5 => 'sunday'],
        ]);

        $this->assertSame(['wednesday', 'sunday'], $data['weekdays']);
    }

    // ------------------------------------------- inline instructor creation

    #[Test]
    public function the_instructor_select_offers_an_inline_create_option(): void
    {
        $schema = ClassesForm::configure(Schema::make());

        $select = null;

        $find = function (array $components) use (&$find, &$select): void {
            foreach ($components as $component) {
                if ($component instanceof Select && $component->getName() === 'instructor_id') {
                    $select = $component;
                }

                if (method_exists($component, 'getDefaultChildComponents')) {
                    $find($component->getDefaultChildComponents());
                }
            }
        };

        $find($schema->getComponents());

        $this->assertNotNull($select, 'instructor_id select is missing from the form.');
        $this->assertTrue(
            $select->hasCreateOptionActionFormSchema(),
            'instructor_id should let an admin add an instructor without leaving the page.'
        );
    }

    #[Test]
    public function creating_an_instructor_stores_both_locales(): void
    {
        $id = ClassesForm::createInstructor(['name' => ['en' => 'Sara', 'ar' => 'سارة']]);

        $instructor = Instructor::findOrFail($id);

        $this->assertSame('Sara', $instructor->getTranslation('name', 'en'));
        $this->assertSame('سارة', $instructor->getTranslation('name', 'ar'));
    }

    #[Test]
    public function a_blank_arabic_name_falls_back_to_the_english_one(): void
    {
        // Otherwise the Arabic panel would render an option with an empty label.
        $id = ClassesForm::createInstructor(['name' => ['en' => 'Sara', 'ar' => '  ']]);

        $instructor = Instructor::findOrFail($id);

        $this->assertSame('Sara', $instructor->getTranslation('name', 'ar'));
    }

    #[Test]
    public function a_missing_arabic_key_is_tolerated(): void
    {
        $id = ClassesForm::createInstructor(['name' => ['en' => 'Sara']]);

        $this->assertSame('Sara', Instructor::findOrFail($id)->getTranslation('name', 'ar'));
    }
}
