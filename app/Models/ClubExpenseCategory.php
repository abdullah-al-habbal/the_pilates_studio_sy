<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class ClubExpenseCategory extends Model
{
    use HasFactory;

    public function expenses(): HasMany
    {
        return $this->hasMany(ClubExpense::class, 'category_id');
    }
}
