<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Operations;

use App\Enums\BookingStatusEnum;
use App\Enums\UserStatusEnum;
use App\Http\Resources\Admin\Operations\ClientListItemResource;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClientListStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_list_exposes_frozen_status_when_booking_is_frozen(): void
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
        ]);

        $user = User::with(['bookings.package', 'frozenCreditBooking.package'])
            ->findOrFail($client->id);

        $data = (new ClientListItemResource($user))->resolve();

        $this->assertSame('frozen', $data['status']);
        $this->assertNotNull($data['frozen_package']);
    }
}
