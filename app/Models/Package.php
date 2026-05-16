<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\PackageTypeEnum;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;
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
 * @property-read int|null $base_price
 * @property-read bool $is_available_for_purchase
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

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'total_credits',
        'is_active',
        'type',
        'generated_reason',
        'validity_days',
    ];

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'validity_days' => 'integer',
            'is_active' => 'boolean',
            'type' => PackageTypeEnum::class,
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function getPriceForCurrency(int $currencyId): ?int
    {
        $pricing = app(PricingService::class);
        $basePrice = $pricing->getBasePrice($this);

        if ($basePrice === null) {
            return null;
        }

        $baseCurrencyId = $pricing->getBaseCurrencyId();

        if ($currencyId === $baseCurrencyId) {
            return $basePrice;
        }

        return $pricing->calculateAmount($basePrice, $currencyId);
    }

    public function getBasePrice(): ?int
    {
        return app(PricingService::class)->getBasePrice($this);
    }

    public function isSystemGenerated(): bool
    {
        return $this->type !== PackageTypeEnum::STANDARD;
    }

    protected function isAvailableForPurchase(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $user = Auth::user();

                if (!$user || !$this->is_active) {
                    return false;
                }

                return !$user->bookings()
                    ->where('status', BookingStatusEnum::ACTIVE)
                    ->where('remaining_credits', '>', 0)
                    ->exists();
            }
        );
    }


}
