<?php
declare(strict_types=1);
namespace App\Handlers\Finance;
use App\Models\ClubExpense;
use App\Models\ClubExpenseCategory;
use Illuminate\Support\Facades\DB;
class RecordClubExpenseHandler
{
    /**
     * Record a new club expense; creates category if name is new
     *
     * @param array{category_name:string,amount:int,notes?:string,recorded_by:int,expense_date?:string} $data
     */
    public function handle(array $data): ClubExpense
    {
        return DB::transaction(function () use ($data) {
            $category = ClubExpenseCategory::firstOrCreate(
                ['name' => $data['category_name']],
                ['name' => $data['category_name']]
            );
            return ClubExpense::create([
                'category_id' => $category->id,
                'amount' => (int) $data['amount'],
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $data['recorded_by'],
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            ]);
        });
    }
}
