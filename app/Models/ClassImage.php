<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'class_id',
    'url',
    'is_primary',
])]
class ClassImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->url)) {
                    return null;
                }

                if (str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://')) {
                    return $this->url;
                }

                return Storage::disk('public')->url($this->url);
            },
        );
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
