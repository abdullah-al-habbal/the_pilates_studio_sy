<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingSourceTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\PricingService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * @property-read float|null $exchange_rate_snapshot Immutable rate at transaction time for audit accuracy
 * @property int|null $validity_days_snapshot Snapshot of package validity_days at purchase time
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'created_by',
        'package_id',
        'total_credits',
        'remaining_credits',
        'status',
        'expires_at',
        'paid_amount',
        'currency_id',
        'source_type',
        'parent_booking_id',
        'frozen_at',
        'unfrozen_at',
        'exchange_rate_snapshot',
        'validity_days_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'remaining_credits' => 'integer',
            'paid_amount' => 'integer',
            'expires_at' => 'datetime',
            'frozen_at' => 'datetime',
            'unfrozen_at' => 'datetime',
            'status' => BookingStatusEnum::class,
            'source_type' => BookingSourceTypeEnum::class,
            'exchange_rate_snapshot' => 'float',
            'validity_days_snapshot' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if ($booking->package_id && !$booking->validity_days_snapshot) {
                $package = Package::find($booking->package_id);
                if ($package) {
                    $booking->validity_days_snapshot = $package->validity_days;

                    if ($package->validity_days > 0 && !$booking->expires_at) {
                        $baseDate = $booking->created_at ?? now();
                        $booking->expires_at = $baseDate->copy()->addDays($package->validity_days);
                    }
                }
            }
        });

        static::saving(function (Booking $booking) {
            if (!$booking->exchange_rate_snapshot) {
                $currencyId = $booking->currency_id
                    ?? app(CurrencyService::class)->getBaseCurrency()->id;
                $booking->exchange_rate_snapshot = app(PricingService::class)
                    ->getExchangeRateForSnapshot($currencyId);
            }
        });
    }

    public function deductCredit(): void
    {
        if ($this->remaining_credits <= 0) {
            throw new InvalidArgumentException('Cannot deduct credit: no credits remaining.');
        }

        $this->decrement('remaining_credits');

        if ($this->remaining_credits <= 0) {
            $this->update(['status' => BookingStatusEnum::EXHAUSTED]);
        }
    }

    public function refundCredit(): void
    {
        if ($this->remaining_credits < $this->total_credits) {
            $this->increment('remaining_credits');
            if ($this->status === BookingStatusEnum::EXHAUSTED) {
                $this->update(['status' => BookingStatusEnum::ACTIVE]);
            }
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === BookingStatusEnum::ACTIVE && !$this->isExpired();
    }

    public function isFrozen(): bool
    {
        return $this->status === BookingStatusEnum::FROZEN;
    }

    public function freeze(): void
    {
        $this->update([
            'status' => BookingStatusEnum::FROZEN,
            'frozen_at' => now(),
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => BookingStatusEnum::ACTIVE,
            'unfrozen_at' => now(),
        ]);
    }

    public function getUsedCreditsAttribute(): int
    {
        return $this->total_credits - $this->remaining_credits;
    }

    public function getCreditsUsagePercentageAttribute(): int
    {
        if ($this->total_credits === 0) {
            return 0;
        }

        return (int) round(($this->used_credits / $this->total_credits) * 100);
    }

    public function getCreditsProgressColorAttribute(): string
    {
        $ratio = $this->remaining_credits / max($this->total_credits, 1);

        return match (true) {
            $ratio > 0.5 => 'success',
            $ratio > 0.2 => 'warning',
            default => 'danger',
        };
    }

    public function getRemainingDaysAttribute(): ?int
    {
        return $this->expires_at ? max(0, (int) now()->diffInDays($this->expires_at, false)) : null;
    }

    protected function hasCreditsRemaining(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->remaining_credits > 0
        );
    }

    protected function canDeductCredit(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->isActive() && $this->remaining_credits > 0
        );
    }

    protected function canBeCancelled(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === BookingStatusEnum::ACTIVE
            && $this->remaining_credits === $this->total_credits
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bookingSessions(): HasMany
    {
        return $this->hasMany(BookingSession::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function parentBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'parent_booking_id');
    }

    public function resumeBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'parent_booking_id');
    }

    protected function isExhausted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === BookingStatusEnum::EXHAUSTED
        );
    }

    protected function isWithinValidity(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->isExpired()
        );
    }

    protected function creditsNearEmpty(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->remaining_credits > 0 && $this->remaining_credits <= 2
        );
    }
}