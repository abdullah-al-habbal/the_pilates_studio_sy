<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WeekdayEnum;
use App\Filament\Admin\Resources\Classes\Pages\CreateClasses;
use App\Filament\Admin\Resources\Classes\Pages\EditClasses;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassImage;
use App\Models\Instructor;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the "images are optional" contract: a class may be created with no
 * images, but every image item an admin adds must carry an uploaded file.
 *
 * `class_images.url` stays NOT NULL — there is no concept of a "blank image"
 * record, only of having zero image records.
 */
final class ClassesImagesOptionalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Instructor $instructor;

    private ClassCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->admin()->create();
        $this->instructor = Instructor::factory()->create();
        $this->category = ClassCategory::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     *
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
        ], $overrides);
    }

    #[Test]
    public function a_class_can_be_created_with_no_images_field_at_all(): void
    {
        Storage::fake('public');

        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Classes::count());
        $this->assertSame(0, ClassImage::count(), 'No image rows may be created for an imageless class.');
    }

    #[Test]
    public function an_explicit_empty_images_list_is_valid(): void
    {
        Storage::fake('public');

        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData(['images' => []]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Classes::count());
        $this->assertSame(0, ClassImage::count());
    }

    #[Test]
    public function an_image_item_without_a_file_is_rejected(): void
    {
        Storage::fake('public');

        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData([
                'images' => [
                    ['url' => null, 'is_primary' => false],
                ],
            ]))
            ->call('create')
            ->assertHasFormErrors(['images.0.url']);

        $this->assertSame(0, Classes::count(), 'A class with an incomplete image item must not be created.');
        $this->assertSame(0, ClassImage::count());
    }

    #[Test]
    public function an_image_item_with_an_uploaded_file_creates_a_class_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('hero.jpg');

        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData([
                'images' => [
                    ['url' => [$file], 'is_primary' => false],
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $class = Classes::firstOrFail();
        $image = $class->images()->firstOrFail();

        $this->assertNotEmpty($image->url, 'A ClassImage must always carry a stored file path.');
        Storage::disk('public')->assertExists($image->url);
    }

    #[Test]
    public function existing_images_survive_an_edit(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('hero.jpg');

        Livewire::test(CreateClasses::class)
            ->fillForm($this->formData([
                'images' => [
                    ['url' => [$file], 'is_primary' => true],
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $class = Classes::firstOrFail();
        $image = $class->images()->firstOrFail();

        Livewire::test(EditClasses::class, ['record' => $class->getKey()])
            ->fillForm($this->formData([
                'images' => [
                    ['url' => [$image->url], 'is_primary' => true],
                ],
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $class->refresh();

        $this->assertSame(1, $class->images()->count());
        $this->assertSame($image->url, $class->images()->firstOrFail()->url);
    }
}
