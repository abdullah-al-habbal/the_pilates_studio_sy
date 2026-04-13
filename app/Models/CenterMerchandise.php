<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class CenterMerchandise extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_quantity',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock_quantity' => 'integer',
        ];
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CenterMerchandiseCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CenterMerchandiseImage::class, 'center_merchandise_id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(CenterMerchandiseImage::class, 'center_merchandise_id')
            ->where('is_primary', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MerchandiseOrder::class, 'merchandise_id');
    }
}