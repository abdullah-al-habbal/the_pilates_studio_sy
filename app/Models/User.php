<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @method PersonalAccessToken|null currentAccessToken()
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'fullname',
        'phone_number',
        'email',
        'password',
        'date_of_birth',
        'email_verified_at',
        'otp_code',
        'otp_expires_at',
        'deactivated_at',
        'deleted_by',
        'status',
        'frozen_at',
        'freeze_reason',
    ];

    protected $hidden = [
        'password',
        'otp_code',
        'otp_expires_at',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'      => 'date',
            'email_verified_at'  => 'datetime',
            'otp_expires_at'     => 'datetime',
            'frozen_at'          => 'datetime',
            'deactivated_at'     => 'datetime',
            'password'           => 'hashed',
            'status'             => UserStatusEnum::class,
            'is_active'          => 'boolean',
        ];
    }

    // ─── Status helpers ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === UserStatusEnum::ACTIVE;
    }

    public function isFrozen(): bool
    {
        return $this->status === UserStatusEnum::FROZEN;
    }

    public function isDeactivatedAccount(): bool
    {
        return $this->status === UserStatusEnum::DEACTIVATED;
    }

    // ─── Computed attributes ─────────────────────────────────────────────────

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->fullname);
    }

    protected function hasCredits(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_remaining_credits > 0
        );
    }

    protected function canBookNewPackage(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->bookings()
                ->where('status', BookingStatusEnum::ACTIVE)
                ->where('remaining_credits', '>', 0)
                ->exists()
        );
    }

    protected function canReserveClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_remaining_credits > 0 && $this->isActive()
        );
    }

    protected function isVerified(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->email_verified_at)
        );
    }

    protected function isDeactivated(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->deactivated_at)
        );
    }

    protected function hasActiveBooking(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->activeCreditBooking)
        );
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getTotalRemainingCreditsAttribute(): int
    {
        return $this->bookings()->where('status', BookingStatusEnum::ACTIVE->value)->sum('remaining_credits');
    }

    public function getAllowNotificationsAttribute(): bool
    {
        return (bool) $this->settings?->allow_notifications;
    }

    public function getFcmTokenAttribute(): ?string
    {
        return $this->settings?->fcm_token;
    }

    public function getPreferredLocaleAttribute(): string
    {
        return $this->settings?->resolvedLocale() ?? config('app.locale', 'en');
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingSessions(): HasManyThrough
    {
        return $this->hasManyThrough(BookingSession::class, Booking::class);
    }

    public function merchandiseOrders(): HasMany
    {
        return $this->hasMany(MerchandiseOrder::class, 'customer_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function activeBooking(): HasOne
    {
        return $this->hasOne(Booking::class)->where('status', BookingStatusEnum::ACTIVE->value)->latest();
    }

    public function activeCreditBooking(): HasOne
    {
        return $this->hasOne(Booking::class)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->latest();
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ClubExpense::class, 'recorded_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
