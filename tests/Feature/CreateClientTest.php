<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRoleEnum;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * POST /admin/operations/clients — "Add new client" in the Clients & Packages tab.
 */
final class CreateClientTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/admin/operations/clients';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Creating a client fires UserRegisteredEvent, whose listener seeds user_settings with
        // the default language and hard-fails without one. Seeded in production; absent under
        // RefreshDatabase.
        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->postJson(self::URI, [
            'fullname' => 'Nour Haddad',
            'phone_number' => '0955000111',
            ...$overrides,
        ]);
    }

    #[Test]
    public function it_is_unreachable_without_authentication(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }

    #[Test]
    public function it_creates_a_customer(): void
    {
        $this->submit(['email' => 'nour@example.test', 'date_of_birth' => '1994-03-02'])
            ->assertCreated();

        $client = User::where('phone_number', '0955000111')->sole();

        $this->assertSame('Nour Haddad', $client->fullname);
        $this->assertSame(UserRoleEnum::CUSTOMER, $client->role);
        $this->assertSame('1994-03-02', $client->date_of_birth->toDateString());
    }

    #[Test]
    public function the_default_password_is_usable(): void
    {
        // Regression guard: User casts `password` as 'hashed', so hashing in the handler as well
        // would store a hash of a hash and lock the client out of their own account.
        $this->submit()->assertCreated();

        $client = User::where('phone_number', '0955000111')->sole();

        $this->assertTrue(Hash::check('pilates', $client->password));
    }

    #[Test]
    public function a_supplied_password_is_stored_usably(): void
    {
        $this->submit(['password' => 'sunflower22'])->assertCreated();

        $this->assertTrue(
            Hash::check('sunflower22', User::where('phone_number', '0955000111')->sole()->password),
        );
    }

    #[Test]
    public function it_seeds_the_default_user_settings_row(): void
    {
        // Goes through UserEloquentRepository::create() for exactly this side effect.
        $this->submit()->assertCreated();

        $this->assertNotNull(User::where('phone_number', '0955000111')->sole()->settings);
    }

    #[Test]
    public function fullname_and_phone_are_required(): void
    {
        $this->actingAs($this->admin)->postJson(self::URI, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fullname', 'phone_number']);
    }

    #[Test]
    public function a_phone_already_held_by_an_active_account_is_rejected(): void
    {
        User::factory()->create(['phone_number' => '0955000111']);

        $this->submit()
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone_number');
    }

    #[Test]
    public function a_phone_freed_by_a_soft_deleted_account_is_accepted(): void
    {
        // The DB uniques are on (phone_number, is_active) where is_active is generated from
        // deleted_at, so a soft-deleted account releases its number. An unscoped unique rule
        // would refuse a value the database would happily take.
        User::factory()->create(['phone_number' => '0955000111'])->delete();

        $this->submit()->assertCreated();
    }

    #[Test]
    public function an_email_already_held_by_an_active_account_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->submit(['email' => 'taken@example.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    #[Test]
    public function email_and_date_of_birth_are_optional(): void
    {
        $this->submit()->assertCreated();

        $client = User::where('phone_number', '0955000111')->sole();

        $this->assertNull($client->email);
        $this->assertNull($client->date_of_birth);
    }

    #[Test]
    public function a_future_date_of_birth_is_rejected(): void
    {
        $this->submit(['date_of_birth' => now()->addYear()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_of_birth');
    }
}
