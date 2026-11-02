<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\CenterMerchandise;
use App\Models\Currency;
use App\Models\Language;
use App\Models\MerchandiseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * POST /admin/operations/store/orders — "Quick Order" from the client details modal.
 */
final class QuickOrderTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/admin/operations/store/orders';

    private User $admin;

    private User $client;

    private Currency $usd;

    private Currency $syp;

    private CenterMerchandise $item;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);

        $this->usd = Currency::query()->firstOrCreate(
            ['code' => strtoupper((string) config('currency.base_currency'))],
            [
                'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'decimal_places' => 2,
                'exchange_rate' => 1.0,
                'is_active' => true,
            ],
        );
        $this->syp = Currency::factory()->create([
            'code' => 'SYP',
            'decimal_places' => 0,
            'exchange_rate' => 15.0,
        ]);

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);
        $this->client = User::factory()->create();

        $this->item = CenterMerchandise::factory()->create(['stock_quantity' => 10]);
        $this->item->prices()->create(['currency_id' => $this->usd->id, 'amount' => 5000]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function order(array $overrides = [], ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->admin)->postJson(self::URI, [
            'customer_id' => $this->client->id,
            'merchandise_id' => $this->item->id,
            'quantity' => 2,
            'currency_id' => $this->usd->id,
            ...$overrides,
        ]);
    }

    #[Test]
    public function it_requires_authentication_and_the_admin_role(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();

        $this->order(as: $this->client)->assertForbidden();
        $this->assertSame(0, MerchandiseOrder::count());
    }

    #[Test]
    public function it_places_a_paid_order_and_decrements_stock(): void
    {
        $this->order()->assertCreated();

        $order = MerchandiseOrder::sole();
        $this->assertSame($this->client->id, $order->customer_id);
        $this->assertSame($this->admin->id, $order->created_by);
        $this->assertSame(2, $order->quantity);
        $this->assertSame(10000, $order->paid_amount);
        $this->assertEquals(1.0, $order->exchange_rate_snapshot);
        $this->assertSame(5000, $order->merchandise_unit_price_snapshot);
        $this->assertSame(8, $this->item->fresh()->stock_quantity);
    }

    #[Test]
    public function it_converts_to_the_chosen_currency_and_snapshots_the_rate(): void
    {
        $this->order(['currency_id' => $this->syp->id])->assertCreated();

        $order = MerchandiseOrder::sole();
        $this->assertSame($this->syp->id, $order->currency_id);
        $this->assertSame(5000 * 2 * 15, $order->paid_amount);
        $this->assertEquals(15.0, $order->exchange_rate_snapshot);
    }

    #[Test]
    public function it_rejects_more_than_the_available_stock_without_side_effects(): void
    {
        $this->order(['quantity' => 11])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertSame(0, MerchandiseOrder::count());
        $this->assertSame(10, $this->item->fresh()->stock_quantity);
    }

    #[Test]
    public function it_allows_the_last_unit_and_then_blocks_the_next_order(): void
    {
        $this->order(['quantity' => 10])->assertCreated();
        $this->assertSame(0, $this->item->fresh()->stock_quantity);

        $this->order(['quantity' => 1])->assertUnprocessable();
        $this->assertSame(1, MerchandiseOrder::count());
    }

    #[Test]
    public function it_allows_a_frozen_client_to_buy(): void
    {
        $this->client->update(['status' => UserStatusEnum::FROZEN, 'frozen_at' => now()]);

        $this->order()->assertCreated();
        $this->assertSame(1, MerchandiseOrder::where('customer_id', $this->client->id)->count());
    }

    #[Test]
    public function it_blocks_deactivated_deleted_and_non_client_accounts(): void
    {
        $byStatus = User::factory()->create(['status' => UserStatusEnum::DEACTIVATED]);
        $byTimestamp = User::factory()->deactivated()->create();
        $deleted = User::factory()->create();
        $deleted->delete();
        $staff = User::factory()->create(['role' => UserRoleEnum::ADMIN->value]);

        foreach (['status' => $byStatus, 'timestamp' => $byTimestamp, 'deleted' => $deleted, 'staff' => $staff] as $label => $customer) {
            $this->order(['customer_id' => $customer->id])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('customer_id');
        }

        $this->assertSame(0, MerchandiseOrder::count());
        $this->assertSame(10, $this->item->fresh()->stock_quantity);
    }

    #[Test]
    public function it_rejects_inactive_currencies_and_deleted_items(): void
    {
        $inactive = Currency::factory()->create(['is_active' => false]);
        $this->order(['currency_id' => $inactive->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');

        $this->item->delete();
        $this->order()->assertUnprocessable()->assertJsonValidationErrors('merchandise_id');

        $this->assertSame(0, MerchandiseOrder::count());
    }

    #[Test]
    public function it_rejects_an_item_without_a_base_price(): void
    {
        $unpriced = CenterMerchandise::factory()->create(['stock_quantity' => 5]);

        $this->order(['merchandise_id' => $unpriced->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('merchandise_id');

        $this->assertSame(5, $unpriced->fresh()->stock_quantity);
    }

    #[Test]
    public function it_validates_quantity_bounds(): void
    {
        foreach ([0, -1, 1001, 'abc', 1.5] as $quantity) {
            $this->order(['quantity' => $quantity])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('quantity');
        }
    }

    #[Test]
    public function client_details_report_the_purchase_currency_and_survive_a_deleted_item(): void
    {
        $this->order(['currency_id' => $this->syp->id])->assertCreated();
        $name = $this->item->getTranslation('name', 'en');
        $this->item->delete();

        $purchase = $this->actingAs($this->admin)
            ->getJson("/admin/operations/clients/{$this->client->id}/details")
            ->assertOk()
            ->json('data.store_purchases.0');

        $this->assertSame($this->syp->id, $purchase['currency_id']);
        $this->assertSame(5000 * 2 * 15, $purchase['total_price']);
        $this->assertSame($name, $purchase['item_name']);
    }
}
