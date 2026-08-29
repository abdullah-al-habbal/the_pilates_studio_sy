<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
#[Fillable([
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
    'role',
    'frozen_at',
    'freeze_reason',
])]
#[Hidden([
    'password',
    'otp_code',
    'otp_expires_at',
    'remember_token',
])]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'frozen_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'role' => UserRoleEnum::class,
            'is_active' => 'boolean',
        ];
    }

    // ─── Role helpers ─────────────────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->isAdmin()
            && $this->isActive()
            && ! $this->trashed();
    }

    public function isMainAdmin(): bool
    {
        return $this->role === UserRoleEnum::MAIN_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRoleEnum::MAIN_ADMIN, UserRoleEnum::ADMIN], true);
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRoleEnum::CUSTOMER;
    }

    #[Scope]
    protected function customers(Builder $query): void
    {
        $query->where('role', UserRoleEnum::CUSTOMER->value);
    }

    #[Scope]
    protected function admins(Builder $query): void
    {
        $query->whereIn('role', [UserRoleEnum::MAIN_ADMIN->value, UserRoleEnum::ADMIN->value]);
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

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->fullname);
    }

    protected function hasCredits(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_remaining_credits > 0,
        );
    }

    protected function canBookNewPackage(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->bookings()
                ->where('status', BookingStatusEnum::ACTIVE)
                ->where('remaining_credits', '>', 0)
                ->exists(),
        );
    }

    protected function canReserveClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_remaining_credits > 0 && $this->isActive(),
        );
    }

    protected function isVerified(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->email_verified_at),
        );
    }

    protected function isDeactivated(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->deactivated_at),
        );
    }

    protected function hasActiveBooking(): Attribute
    {
        return Attribute::make(
            get: fn () => ! is_null($this->activeCreditBooking),
        );
    }

    protected function totalRemainingCredits(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->bookings()
                ->where('status', BookingStatusEnum::ACTIVE->value)
                ->sum('remaining_credits'),
        );
    }

    protected function allowNotifications(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) $this->settings?->allow_notifications,
        );
    }

    protected function fcmToken(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->settings?->fcm_token,
        );
    }

    protected function preferredLocale(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->settings?->resolvedLocale() ?? config('app.locale', 'en'),
        );
    }

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

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function activeBooking(): HasOne
    {
        return $this->hasOne(Booking::class)
            ->where('status', BookingStatusEnum::ACTIVE->value)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    /**
     * The booking, if any, occupying `active_user_id` and therefore blocking a new purchase.
     *
     * Deliberately WITHOUT the expiry clause its neighbour above carries: this must match the
     * generated column behind `unique_active_booking_per_user`, which tests status and credits
     * only. `activeCreditBooking()` is the right shape for "what package can this client use
     * today" — this one answers "what would the database refuse", which is a different question.
     *
     * @see docs/historical-backfill/findings/A16-booking-insert-paths.md
     */
    public function blockingActiveBooking(): HasOne
    {
        return $this->hasOne(Booking::class)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->latest();
    }

    public function activeCreditBooking(): HasOne
    {
        return $this->hasOne(Booking::class)
            ->where('status', BookingStatusEnum::ACTIVE)
            ->where('remaining_credits', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    public function frozenCreditBooking(): HasOne
    {
        return $this->hasOne(Booking::class)
            ->where('status', BookingStatusEnum::FROZEN)
            ->whereNull('unfrozen_at')
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
