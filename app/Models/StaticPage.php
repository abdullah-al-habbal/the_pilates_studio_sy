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

#[Fillable(['slug', 'title', 'image', 'content', 'is_active', 'sort_order'])]
#[Translatable(['title', 'content'])]
class StaticPage extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null,
        );
    }
}
