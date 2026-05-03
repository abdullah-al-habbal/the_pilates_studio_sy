<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Currency;
use App\Models\MerchandiseOrder;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundFactory extends Factory
{
    protected $model = Refund::class;

    public function definition(): array
    {
        $refundableType = $this->faker->randomElement([
            Booking::class,
            MerchandiseOrder::class,
        ]);

        return [
            'refundable_type' => (new $refundableType())->getTable(),
            'refundable_id'   => $refundableType::factory(),
            'user_id'         => User::factory(),
            'currency_id'     => Currency::factory()->active(),
            'amount'          => $this->faker->numberBetween(500, 20000),
            'reason'          => $this->faker->optional()->sentence(),
            'refunded_by'     => User::factory(),
            'refunded_at'     => now(),
        ];
    }
}
