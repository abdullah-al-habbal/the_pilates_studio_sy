<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'name',
    'role',
    'quote',
    'avatar',
    'rating',
    'is_active',
    'sort_order',
])]
#[Translatable(['name', 'role', 'quote'])]
class Testimonial extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
        );
    }
}
