<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title', 'content'];

    protected $fillable = ['slug', 'title', 'image', 'content'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? url($value) : null
        );
    }
}
