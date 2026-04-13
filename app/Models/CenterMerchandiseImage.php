<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterMerchandiseImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_merchandise_id',
        'url',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function merchandise(): BelongsTo
    {
        return $this->belongsTo(CenterMerchandise::class, 'center_merchandise_id');
    }
}