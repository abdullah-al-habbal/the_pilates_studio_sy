<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class RecurrencePattern extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['label'];

    protected $fillable = ['name', 'label', 'interval_days'];

    protected function casts(): array
    {
        return ['interval_days' => 'integer'];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }
}
