<?php
declare(strict_types=1);
namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Translatable\HasTranslations;

class CenterMerchandise extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'stock_quantity',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
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

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function getPriceForCurrency(int $currencyId): ?int
    {
        return $this->prices
            ->firstWhere('currency_id', $currencyId)
                ?->amount;
    }
    private function getCurrentCurrencyId(): int
    {
        return app(CurrencyService::class)->getDefaultCurrency()->id;
    }
    public function getPriceForCurrentCurrency(): ?int
    {
        $currencyId = $this->getCurrentCurrencyId();

        return $this->prices()
            ->where('currency_id', $currencyId)
            ->value('amount');
    }
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn(): ?int => $this->getPriceForCurrentCurrency()
        );
    }
}