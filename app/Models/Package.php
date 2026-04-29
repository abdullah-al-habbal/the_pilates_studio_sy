<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\PackageTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Package extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'total_credits', 'price', 'is_active', 'type', 'generated_reason', 'validity_days'];

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'price' => 'integer',
            'validity_days' => 'integer',
            'is_active' => 'boolean',
            'type' => PackageTypeEnum::class,
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PackagePrice::class);
    }

    public function priceForCurrency(string $currencyCode): ?int
    {
        return $this->prices
            ->firstWhere('currency.code', $currencyCode)
            ?->amount;
    }

    public function isSystemGenerated(): bool
    {
        return $this->type !== PackageTypeEnum::STANDARD;
    }

    protected function isAvailableForPurchase(): Attribute
    {
        return Attribute::make(
            get: function () {
                $user = auth()->user();
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

    protected function isCheapestOption(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active
                && $this->price === self::where('is_active', true)->min('price')
        );
    }
}
