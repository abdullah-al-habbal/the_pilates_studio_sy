<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\PackageTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property array $name
 * @property int $total_credits
 * @property bool $is_active
 * @property PackageTypeEnum $type
 * @property string|null $generated_reason
 * @property int|null $validity_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Price> $prices
 * @property-read int|null $price
 * @property-read bool $is_available_for_purchase
 * @property-read bool $is_cheapest_option
 *
 * @method static Builder|Package newModelQuery()
 * @method static Builder|Package newQuery()
 * @method static Builder|Package query()
 * @method static Builder|Package whereCreatedAt($value)
 * @method static Builder|Package whereDeletedAt($value)
 * @method static Builder|Package whereGeneratedReason($value)
 * @method static Builder|Package whereId($value)
 * @method static Builder|Package whereIsActive($value)
 * @method static Builder|Package whereName($value)
 * @method static Builder|Package whereTotalCredits($value)
 * @method static Builder|Package whereType($value)
 * @method static Builder|Package whereUpdatedAt($value)
 * @method static Builder|Package whereValidityDays($value)
 */
class Package extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'total_credits',
        'is_active',
        'type',
        'generated_reason',
        'validity_days',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'validity_days' => 'integer',
            'is_active' => 'boolean',
            'type' => PackageTypeEnum::class,
        ];
    }

    /**
     * Get the bookings for this package.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the prices for this package.
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
     * Check if the package is system generated.
     *
     * @return bool
     */
    public function isSystemGenerated(): bool
    {
        return $this->type !== PackageTypeEnum::STANDARD;
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
     * Get the cheapest package price for the current currency.
     *
     * @return int|null
     */
    private function getCheapestPriceForCurrentCurrency(): ?int
    {
        $currencyId = $this->getCurrentCurrencyId();

        $cheapestAmount = Package::query()->where('is_active', true)
            ->whereHas('prices', function ($query) use ($currencyId) {
                $query->where('currency_id', $currencyId);
            })
            ->with(['prices' => function ($query) use ($currencyId) {
                $query->where('currency_id', $currencyId);
            }])
            ->get()
            ->min(function (Package $package) {
                $price = $package->prices->first();
                return $price?->amount ?? PHP_INT_MAX;
            });

        return $cheapestAmount !== PHP_INT_MAX ? $cheapestAmount : null;
    }

    /**
     * Accessor for is_available_for_purchase attribute.
     * Determines if the package can be purchased by the current user.
     *
     * @return Attribute<bool, never>
     */
    protected function isAvailableForPurchase(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $user = Auth::user();

                if (! $user || ! $this->is_active) {
                    return false;
                }

                return ! $user->bookings()
                    ->where('status', BookingStatusEnum::ACTIVE)
                    ->where('remaining_credits', '>', 0)
                    ->exists();
            }
        );
    }

    /**
     * Accessor for is_cheapest_option attribute.
     * Determines if this package is the cheapest available option.
     *
     * @return Attribute<bool, never>
     */
    protected function isCheapestOption(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (! $this->is_active) {
                    return false;
                }

                $currentPrice = $this->getPriceForCurrentCurrency();
                $cheapestPrice = $this->getCheapestPriceForCurrentCurrency();

                if ($currentPrice === null || $cheapestPrice === null) {
                    return false;
                }

                return $currentPrice === $cheapestPrice;
            }
        );
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
