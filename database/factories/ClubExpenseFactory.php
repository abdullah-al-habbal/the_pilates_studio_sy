<?php

namespace Database\Factories;

use App\Enums\ClubExpenseStatusEnum;
use App\Models\ClubExpense;
use App\Models\ClubExpenseCategory;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClubExpenseFactory extends Factory
{
    protected $model = ClubExpense::class;

    public function definition(): array
    {
        return [
            'category_id' => ClubExpenseCategory::factory(),
            'currency_id' => Currency::factory()->active(),
            'amount' => $this->faker->numberBetween(100, 5000),
            'notes' => $this->faker->optional()->sentence(),
            'recorded_by' => User::factory(),
            'expense_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'status' => ClubExpenseStatusEnum::PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }
}
