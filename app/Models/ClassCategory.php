<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'name',
    'slug',
    'color',
])]
#[Translatable(['name'])]
class ClassCategory extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }
}
