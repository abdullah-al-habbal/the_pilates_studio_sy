<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'label', 'interval_days'])]
#[Translatable(['label'])]
class RecurrencePattern extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return ['interval_days' => 'integer'];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }
}
