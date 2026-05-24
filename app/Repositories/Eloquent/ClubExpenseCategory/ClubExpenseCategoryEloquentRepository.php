<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\ClubExpenseCategory;

use App\Models\ClubExpenseCategory;

final class ClubExpenseCategoryEloquentRepository
{
    public function firstOrCreateByName(string $name): ClubExpenseCategory
    {
        return ClubExpenseCategory::firstOrCreate(
            ['name' => $name],
            ['name' => $name],
        );
    }

    public function getAllNames(): array
    {
        return ClubExpenseCategory::query()->pluck('name')->toArray();
    }
}
