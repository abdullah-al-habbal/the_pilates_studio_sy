<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\ClubExpense;
use App\Models\ClubExpenseCategory;
use Illuminate\Support\Facades\DB;

class ClubExpenseService
{
    public function record(
        string $categoryName,
        int $amount,
        int $recordedBy,
        ?string $notes = null,
        ?\DateTimeInterface $date = null
    ): ClubExpense {
        return DB::transaction(function () use ($categoryName, $amount, $recordedBy, $notes, $date): ClubExpense {
            $category = ClubExpenseCategory::firstOrCreate(['name' => $categoryName]);

            return ClubExpense::create([
                'category_id'    => $category->id,
                'amount'         => $amount,
                'notes'          => $notes,
                'recorded_by'    => $recordedBy,
                'expense_date'   => $date ?? today(),
            ]);
        });
    }
    
    public function getDailyTotal(\DateTimeInterface $date): int
    {
        return (int) ClubExpense::whereDate('expense_date', $date)->sum('amount');
    }
}
