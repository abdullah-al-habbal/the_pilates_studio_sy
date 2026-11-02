<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Direct edit of an assigned package from the client profile.
 */
final class BookingEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
        $this->client = User::factory()->create();
    }

    private function booking(array $overrides = []): Booking
    {
        return Booking::factory()->create([
            'user_id' => $this->client->id,
            'package_id' => Package::factory()->create(['total_credits' => 4])->id,
            'total_credits' => 4,
            'remaining_credits' => 2,
            'status' => BookingStatusEnum::ACTIVE->value,
            'purchased_at' => now()->subDays(20),
            'expires_at' => now()->addDays(10),
            ...$overrides,
        ]);
    }

    private function edit(Booking $booking, array $payload, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)
            ->putJson("/admin/operations/bookings/{$booking->id}", $payload);
    }

    #[Test]
    public function moving_the_expiry_into_the_past_expires_the_booking(): void
    {
        $booking = $this->booking();

        $this->edit($booking, [
            'purchased_at' => now()->subDays(30)->toDateString(),
            'expires_at' => now()->subDays(2)->toDateString(),
        ])->assertOk();

        $this->assertSame(BookingStatusEnum::EXPIRED, $booking->fresh()->status);
    }

    #[Test]
    public function extending_an_expired_booking_reactivates_it(): void
    {
        $booking = $this->booking([
            'status' => BookingStatusEnum::EXPIRED->value,
            'expires_at' => now()->subDays(2),
        ]);

        $this->edit($booking, [
            'purchased_at' => now()->subDays(20)->toDateString(),
            'expires_at' => now()->addDays(5)->toDateString(),
        ])->assertOk();

        $this->assertSame(BookingStatusEnum::ACTIVE, $booking->fresh()->status);
    }

    #[Test]
    public function reactivation_is_refused_when_the_client_has_another_active_package(): void
    {
        $expired = $this->booking([
            'status' => BookingStatusEnum::EXPIRED->value,
            'expires_at' => now()->subDays(2),
        ]);
        $this->booking();

        $this->edit($expired, [
            'purchased_at' => now()->subDays(20)->toDateString(),
            'expires_at' => now()->addDays(5)->toDateString(),
        ])->assertStatus(422);

        $this->assertSame(BookingStatusEnum::EXPIRED, $expired->fresh()->status);
    }

    #[Test]
    public function money_and_credits_cannot_be_edited(): void
    {
        $booking = $this->booking(['paid_amount' => 100]);

        $this->edit($booking, [
            'purchased_at' => now()->subDays(20)->toDateString(),
            'expires_at' => now()->addDays(10)->toDateString(),
            'paid_amount' => 1,
            'total_credits' => 99,
            'remaining_credits' => 99,
        ])->assertOk();

        $fresh = $booking->fresh();
        $this->assertSame(100, $fresh->paid_amount);
        $this->assertSame(4, $fresh->total_credits);
        $this->assertSame(2, $fresh->remaining_credits);
    }

    #[Test]
    public function a_cancelled_booking_cannot_be_edited(): void
    {
        $booking = $this->booking(['status' => BookingStatusEnum::CANCELLED->value]);

        $this->edit($booking, [
            'purchased_at' => now()->subDays(20)->toDateString(),
            'expires_at' => now()->addDays(10)->toDateString(),
        ])->assertStatus(422);
    }

    #[Test]
    public function an_expiry_before_the_purchase_date_is_rejected(): void
    {
        $booking = $this->booking();

        $this->edit($booking, [
            'purchased_at' => now()->subDays(5)->toDateString(),
            'expires_at' => now()->subDays(9)->toDateString(),
        ])->assertStatus(422);
    }

    #[Test]
    public function a_client_cannot_edit_a_booking(): void
    {
        $booking = $this->booking();

        $response = $this->edit($booking, [
            'purchased_at' => now()->subDays(20)->toDateString(),
            'expires_at' => now()->addDays(60)->toDateString(),
        ], $this->client);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(
            now()->addDays(10)->toDateString(),
            $booking->fresh()->expires_at->toDateString(),
        );
    }
}
