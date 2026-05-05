<?php

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $adam = User::where('email', 'adam.kim@gmail.com')->first();
        $package = Package::where('name->en', '12 Sessions Pack')->first();

        if (!$adam || !$package) {
            throw new RuntimeException('BookingSeeder dependency missing: User or Package.');
        }

        $adamHasActive = $adam->bookings()
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->exists();

        $sypCurrency = Currency::where('code', 'SYP')->first();

        if (!$adamHasActive) {
            Booking::create([
                'user_id' => $adam->id,
                'package_id' => $package->id,
                'total_credits' => $package->total_credits,
                'remaining_credits' => 8,
                'status' => BookingStatusEnum::ACTIVE,
                'expires_at' => now()->addMonths(6),
                'paid_amount' => 54000,
                'currency_id' => $sypCurrency?->id,
            ]);
        }

        $otherUsers = User::where('id', '!=', $adam->id)->get();
        if ($otherUsers->isNotEmpty()) {
            $usersForActive = $otherUsers->filter(fn($user) => rand(1, 100) <= 30);

            foreach ($usersForActive as $user) {
                $alreadyActive = $user->bookings()
                    ->where('status', BookingStatusEnum::ACTIVE)
                    ->where('remaining_credits', '>', 0)
                    ->exists();

                if ($alreadyActive) {
                    continue;
                }

                $randomPackage = Package::inRandomOrder()->first();
                if ($randomPackage) {
                    Booking::create([
                        'user_id' => $user->id,
                        'package_id' => $randomPackage->id,
                        'total_credits' => $randomPackage->total_credits,
                        'remaining_credits' => rand(1, $randomPackage->total_credits),
                        'status' => BookingStatusEnum::ACTIVE,
                        'expires_at' => now()->addMonths(rand(1, 12)),
                        'paid_amount' => rand(20000, 100000),
                        'currency_id' => $sypCurrency?->id,
                    ]);
                }
            }
        }

        Booking::factory(15)->exhausted()->create();
        Booking::factory(5)->expired()->create();
        Booking::factory(3)->cancelled()->create();
    }
}
