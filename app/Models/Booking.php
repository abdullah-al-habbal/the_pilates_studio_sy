<?php

// filePath: app/Models/Booking.php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'total_credits',
        'remaining_credits',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'remaining_credits' => 'integer',
            'expires_at' => 'datetime',
            'status' => BookingStatusEnum::class,
        ];
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function bookingSessions(): HasMany
    {
        return $this->hasMany(BookingSession::class);
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

    protected function isExhausted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === BookingStatusEnum::EXHAUSTED
            || $this->remaining_credits <= 0
        );
    }

    protected function isWithinValidity(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->isExpired() && $this->isActive()
        );
    }

    protected function creditsNearEmpty(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->remaining_credits > 0 && $this->remaining_credits <= 2
        );
    }
}
