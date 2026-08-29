<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingSourceTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Observers\BookingObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $created_by
 * @property int|null $package_id
 * @property int $total_credits
 * @property int $remaining_credits
 * @property BookingStatusEnum $status
 * @property Carbon|null $expires_at
 * @property Carbon $purchased_at Business date — when the customer bought.
 *                                All financial reporting filters on this; created_at is audit-only.
 * @property int $paid_amount
 * @property int|null $currency_id
 * @property BookingSourceTypeEnum $source_type
 * @property int|null $parent_booking_id
 * @property Carbon|null $frozen_at
 * @property Carbon|null $unfrozen_at
 * @property-read float|null $exchange_rate_snapshot Immutable rate at transaction time for audit accuracy
 * @property int|null $validity_days_snapshot Snapshot of package validity_days at purchase time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read User|null $createdBy
 * @property-read Package|null $package
 * @property-read Currency|null $currency
 * @property-read Booking|null $parentBooking
 * @property-read Collection<int, BookingSession> $bookingSessions
 * @property-read Collection<int, Refund> $refunds
 * @property-read Collection<int, Booking> $resumeBookings
 * @property-read int $used_credits
 * @property-read int $credits_usage_percentage
 * @property-read string $credits_progress_color
 * @property-read int|null $remaining_days
 * @property-read bool $has_credits_remaining
 * @property-read bool $can_deduct_credit
 * @property-read bool $can_be_cancelled
 * @property-read bool $is_exhausted
 * @property-read bool $is_within_validity
 * @property-read bool $credits_near_empty
 */
#[Fillable([
    'user_id',
    'created_by',
    'package_id',
    'total_credits',
    'remaining_credits',
    'status',
    'expires_at',
    'purchased_at',
    'paid_amount',
    'currency_id',
    'source_type',
    'parent_booking_id',
    'frozen_at',
    'unfrozen_at',
    'exchange_rate_snapshot',
    'validity_days_snapshot',
])]
#[ObservedBy([BookingObserver::class])]
class Booking extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'remaining_credits' => 'integer',
            'paid_amount' => 'integer',
            'expires_at' => 'datetime',
            'purchased_at' => 'datetime',
            'frozen_at' => 'datetime',
            'unfrozen_at' => 'datetime',
            'status' => BookingStatusEnum::class,
            'source_type' => BookingSourceTypeEnum::class,
            'exchange_rate_snapshot' => 'float',
            'validity_days_snapshot' => 'integer',
        ];
    }

    #[Scope]
    protected function blockingNewPurchase(
        Builder $query,
        ?int $exceptBookingId = null,
    ): void {
        $query
            ->when(
                $exceptBookingId !== null,
                fn (Builder $query) => $query->where('id', '!=', $exceptBookingId),
            )
            ->where(function (Builder $query): void {
                $query
                    // No expiry clause on the ACTIVE branch: it must match the generated column
                    // behind unique_active_booking_per_user, which tests status and credits only.
                    // With the clause, a stale row slipped past Filament's validation and the
                    // insert then crashed as an unhandled Livewire exception.
                    ->where(function (Builder $query): void {
                        $query
                            ->where('status', BookingStatusEnum::ACTIVE)
                            ->where('remaining_credits', '>', 0);
                    })
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('status', BookingStatusEnum::FROZEN)
                            ->where(
                                fn (Builder $query) => $query
                                    ->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now()),
                            );
                    });
            });
    }

    public function deductCredit(): void
    {
        if ($this->remaining_credits <= 0) {
            throw new InvalidArgumentException(
                'Cannot deduct credit: no credits remaining.',
            );
        }

        $this->decrement('remaining_credits');

        if ($this->remaining_credits <= 0) {
            $this->update([
                'status' => BookingStatusEnum::EXHAUSTED,
            ]);
        }
    }

    public function refundCredit(): void
    {
        if ($this->remaining_credits < $this->total_credits) {
            $this->increment('remaining_credits');

            if ($this->status === BookingStatusEnum::EXHAUSTED) {
                $this->update([
                    'status' => BookingStatusEnum::ACTIVE,
                ]);
            }
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === BookingStatusEnum::ACTIVE
            && ! $this->isExpired();
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

    protected function usedCredits(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->total_credits - $this->remaining_credits,
        );
    }

    protected function creditsUsagePercentage(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->total_credits === 0) {
                    return 0;
                }

                return (int) round(
                    ($this->used_credits / $this->total_credits) * 100,
                );
            },
        );
    }

    protected function creditsProgressColor(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $ratio = $this->remaining_credits
                    / max($this->total_credits, 1);

                return match (true) {
                    $ratio > 0.5 => 'success',
                    $ratio > 0.2 => 'warning',
                    default => 'danger',
                };
            },
        );
    }

    protected function remainingDays(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->expires_at
                ? max(
                    0,
                    (int) now()->diffInDays($this->expires_at, false),
                )
                : null,
        );
    }

    protected function hasCreditsRemaining(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->remaining_credits > 0,
        );
    }

    protected function canDeductCredit(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->isActive()
                && $this->remaining_credits > 0,
        );
    }

    protected function canBeCancelled(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === BookingStatusEnum::ACTIVE
                && $this->remaining_credits === $this->total_credits,
        );
    }

    protected function isExhausted(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === BookingStatusEnum::EXHAUSTED,
        );
    }

    protected function isWithinValidity(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => ! $this->isExpired(),
        );
    }

    protected function creditsNearEmpty(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->remaining_credits > 0
                && $this->remaining_credits <= 2,
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
}
