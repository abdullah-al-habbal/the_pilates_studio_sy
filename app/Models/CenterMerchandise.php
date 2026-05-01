<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Get the prices for this merchandise.
     *
     * @return MorphMany<Price, $this>
     */
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    /**
     * Get the price for a specific currency ID.
     *
     * @param int $currencyId
     * @return int|null
     */
    public function getPriceForCurrency(int $currencyId): ?int
    {
        return $this->prices
            ->firstWhere('currency_id', $currencyId)
            ?->amount;
    }

    /**
     * Get the current currency ID from authenticated user or default (USD).
     *
     * @return int
     */
    private function getCurrentCurrencyId(): int
    {
        $user = Auth::user();

        if ($user && isset($user->currency_id)) {
            return (int) $user->currency_id;
        }

        $usdCurrency = Currency::query()->where('code', 'USD')->first();

        return $usdCurrency?->id ?? 1;
    }

    /**
     * Get the price for the current user's currency.
     *
     * @return int|null
     */
    public function getPriceForCurrentCurrency(): ?int
    {
        $currencyId = $this->getCurrentCurrencyId();

        return $this->prices()
            ->where('currency_id', $currencyId)
            ->value('amount');
    }

    /**
     * Accessor for price attribute (backward compatibility).
     * Returns the price for the current user's currency.
     *
     * @return Attribute<int|null, never>
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn(): ?int => $this->getPriceForCurrentCurrency()
        );
    }
}