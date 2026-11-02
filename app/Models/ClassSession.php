<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Observers\ClassSessionObserver;
use App\Services\BookingSession\CancellationPolicyService;
use App\Support\Time\StudioTime;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'class_id',
    'date',
    'start_time',
    'end_time',
    'total_spots',
    'status',
])]
#[ObservedBy([ClassSessionObserver::class])]
class ClassSession extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_spots' => 'integer',
            'status' => ClassSessionStatusEnum::class,
        ];
    }

    public function isScheduled(): bool
    {
        return $this->status === ClassSessionStatusEnum::SCHEDULED;
    }

    protected function durationMinutes(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) Carbon::parse($this->start_time)
                ->diffInMinutes(Carbon::parse($this->end_time)),
        );
    }

    /**
     * Start/end instants in the studio timezone (null when the stored time is unreadable).
     */
    public function startsAt(): ?CarbonImmutable
    {
        return $this->studioInstant($this->start_time);
    }

    public function endsAt(): ?CarbonImmutable
    {
        return $this->studioInstant($this->end_time);
    }

    private function studioInstant(?string $time): ?CarbonImmutable
    {
        if ($this->date === null || $time === null || $time === '') {
            return null;
        }

        try {
            return StudioTime::at($this->date->format('Y-m-d'), $time);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function isPast(): Attribute
    {
        return Attribute::make(
            get: fn () => ($endsAt = $this->endsAt()) !== null && $endsAt->isPast(),
        );
    }

    protected function isUpcoming(): Attribute
    {
        return Attribute::make(
            get: fn () => ($startsAt = $this->startsAt()) !== null && $startsAt->isFuture(),
        );
    }

    protected function isAvailable(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->class?->isActive() ?? false)
                && $this->isScheduled()
                && ! $this->isFull()
                && ! $this->is_past,
        );
    }

    protected function isWithinCancellationWindow(): Attribute
    {
        return Attribute::make(
            get: fn () => app(CancellationPolicyService::class)->isInsideWindow($this),
        );
    }

    protected function startsSoon(): Attribute
    {
        return Attribute::make(
            get: function () {
                $startsAt = $this->startsAt();

                return $startsAt !== null
                    && $startsAt->between(StudioTime::now(), StudioTime::now()->addHour());
            },
        );
    }

    public function isBookableForUser(User $user): bool
    {
        return $this->is_available && $user->total_remaining_credits > 0;
    }

    public function isCancelableByUser(BookingSession $bookingSession): bool
    {
        return $bookingSession->isReserved()
            && ! $this->is_within_cancellation_window
            && ! $this->is_past;
    }

    protected function availableSpots(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $reserved = $this->bookingSessions()
                    ->where('status', BookingSessionStatusEnum::RESERVED->value)
                    ->count();

                return max(0, $this->total_spots - $reserved);
            },
        );
    }

    public function isFull(): bool
    {
        return $this->available_spots <= 0;
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function bookingSessions(): HasMany
    {
        return $this->hasMany(BookingSession::class, 'class_session_id');
    }
}
