<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'center_merchandise_id',
    'url',
    'is_primary',
])]
class CenterMerchandiseImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    #[Scope]
    protected function primary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    public function merchandise(): BelongsTo
    {
        return $this->belongsTo(CenterMerchandise::class, 'center_merchandise_id');
    }
}
