<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Enums\UserStatusEnum;
use App\Http\Resources\Admin\Operations\ClientDetailsResource;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClientFrozenPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_details_includes_frozen_package_when_booking_is_frozen(): void
    {
        $currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        $package = Package::factory()->create();
        $client = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);

        Booking::factory()->create([
            'user_id' => $client->id,
            'package_id' => $package->id,
            'currency_id' => $currency->id,
            'status' => BookingStatusEnum::FROZEN,
            'frozen_at' => now(),
            'remaining_credits' => 5,
        ]);

        $user = User::with(['activeCreditBooking.package', 'frozenCreditBooking.package'])
            ->findOrFail($client->id);

        $data = (new ClientDetailsResource($user))->resolve();

        $this->assertNull($data['active_package']);
        $this->assertNotNull($data['frozen_package']);
        $this->assertSame('frozen', $data['frozen_package']['status']);
        $this->assertSame('frozen', $data['status']);
    }
}
