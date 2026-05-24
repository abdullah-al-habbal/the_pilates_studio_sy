<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class NotificationTemplate extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['key', 'title', 'body', 'data', 'is_active'];

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
