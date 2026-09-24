<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRoleEnum;
use App\Models\ClassCategory;
use App\Models\Instructor;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * GET /admin/operations/classes/lookup — debounced, case-insensitive, paginated
 * option source backing the searchable combo selects (instructor / category).
 */
final class ClassLookupEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/admin/operations/classes/lookup';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);
    }

    private function getLookup(string $query): TestResponse
    {
        return $this->actingAs($this->admin)->getJson(self::URI . '?' . ltrim($query, '?'));
    }

    #[Test]
    public function it_is_unreachable_without_authentication(): void
    {
        $this->getJson(self::URI . '?type=instructor')->assertUnauthorized();
    }

    #[Test]
    public function it_rejects_unknown_types(): void
    {
        $this->getLookup('type=superhero')->assertUnprocessable();
    }

    #[Test]
    public function it_enforces_per_page_bounds(): void
    {
        $this->getLookup('type=instructor&per_page=2')->assertUnprocessable();
    }

    #[Test]
    public function it_searches_instructors_case_insensitively(): void
    {
        Instructor::factory()->create(['name' => ['en' => 'Sarah Jrame', 'ar' => 'Sarah Jrame']]);
        Instructor::factory()->create(['name' => ['en' => 'Adam Kim', 'ar' => 'Adam Kim']]);

        $this->getLookup('type=instructor&search=sArAh')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.display_name', 'Sarah Jrame');
    }

    #[Test]
    public function it_searches_instructors_in_arabic(): void
    {
        Instructor::factory()->create(['name' => ['en' => 'Sarah Jrame', 'ar' => 'سارة جريم']]);

        $this->getLookup('type=instructor&search=' . urlencode('جريم'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', Instructor::query()->value('id'));
    }

    #[Test]
    public function it_searches_categories(): void
    {
        ClassCategory::factory()->create(['name' => ['en' => 'Reformer', 'ar' => 'Reformer']]);
        ClassCategory::factory()->create(['name' => ['en' => 'Mat', 'ar' => 'Mat']]);

        $this->getLookup('type=category&search=REF')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.display_name', 'Reformer');
    }

    #[Test]
    public function it_returns_localized_display_names(): void
    {
        Language::factory()->create(['code' => 'ar', 'is_active' => true, 'is_default' => false]);
        Instructor::factory()->create(['name' => ['en' => 'Sarah Jrame', 'ar' => 'سارة جريم']]);

        $this->actingAs($this->admin)
            ->withHeader('x-locale', 'ar')
            ->getJson(self::URI . '?type=instructor&search=sarah')
            ->assertOk()
            ->assertJsonPath('data.0.display_name', 'سارة جريم');
    }

    #[Test]
    public function it_exposes_clean_pagination_metadata(): void
    {
        Instructor::factory()->count(6)->create();

        $this->getLookup('type=instructor&per_page=5&page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 6)
            ->assertJsonCount(5, 'data');

        $response = $this->getLookup('type=instructor&per_page=5&page=99');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
        $this->assertNull($response->json('meta.from'));
        $this->assertNull($response->json('meta.to'));
    }
}
