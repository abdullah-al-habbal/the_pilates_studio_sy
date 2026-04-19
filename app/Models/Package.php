<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
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

    protected $fillable = ['name', 'total_credits', 'price', 'is_active'];

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
