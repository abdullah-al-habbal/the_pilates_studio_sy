<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Admin\Resources\ClassCategories\Pages\CreateClassCategory;
use App\Filament\Admin\Resources\ClassCategories\Pages\EditClassCategory;
use App\Models\ClassCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClassCategorySlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->admin()->create());
    }

    #[Test]
    public function creating_a_category_with_a_unique_slug_succeeds(): void
    {
        Livewire::test(CreateClassCategory::class)
            ->fillForm([
                'name' => 'Reformer',
                'slug' => 'reformer',
                'color' => '#FF0000',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('class_categories', ['slug' => 'reformer']);
    }

    #[Test]
    public function creating_a_category_with_a_duplicate_slug_fails_validation(): void
    {
        ClassCategory::factory()->create(['slug' => 'reformer']);

        Livewire::test(CreateClassCategory::class)
            ->fillForm([
                'name' => 'Another Reformer',
                'slug' => 'reformer',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        $this->assertSame(1, ClassCategory::count());
    }

    #[Test]
    public function editing_a_category_while_keeping_its_own_slug_succeeds(): void
    {
        $category = ClassCategory::factory()->create(['slug' => 'reformer']);

        Livewire::test(EditClassCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => 'Updated Reformer',
                'slug' => 'reformer',
                'color' => '#00FF00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Reformer', $category->fresh()->name);
    }

    #[Test]
    public function editing_a_category_to_another_existing_slug_fails_validation(): void
    {
        ClassCategory::factory()->create(['slug' => 'mat']);
        $category = ClassCategory::factory()->create(['slug' => 'reformer']);

        Livewire::test(EditClassCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'slug' => 'mat',
            ])
            ->call('save')
            ->assertHasFormErrors(['slug']);

        $this->assertSame('reformer', $category->fresh()->slug);
    }
}
