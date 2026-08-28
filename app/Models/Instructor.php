<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'name',
    'title',
    'specialty',
    'bio',
    'social_links',
    'image',
])]
#[Translatable(['name', 'title', 'specialty', 'bio'])]
class Instructor extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null,
        );
    }
}
