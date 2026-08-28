<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable(['key', 'title', 'body', 'data', 'is_active'])]
#[Translatable(['title', 'body'])]
class NotificationTemplate extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getResolvedTitle(?string $locale = null): string
    {
        return $this->getTranslation('title', $locale ?? app()->getLocale());
    }

    public function getResolvedBody(?string $locale = null): string
    {
        return $this->getTranslation('body', $locale ?? app()->getLocale());
    }
}
