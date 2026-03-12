<?php

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $adam    = User::where('email', 'adam.kim@gmail.com')->first();
        $package = Package::where('name->en', '12 Sessions Pack')->first();

        if (! $adam || ! $package) {
            throw new RuntimeException('BookingSeeder dependency missing: User or Package.');
        }

        Booking::firstOrCreate(
            ['user_id' => $adam->id, 'package_id' => $package->id],
            [
                'total_credits'     => $package->total_credits,
                'remaining_credits' => 8,
                'status'            => BookingStatusEnum::ACTIVE->value,
                'expires_at'        => now()->addMonths(6),
            ]
        );

        Booking::factory(15)->create();
        Booking::factory(5)->exhausted()->create();
        Booking::factory(3)->expired()->create();
        Booking::factory(2)->cancelled()->create();
    }
}
