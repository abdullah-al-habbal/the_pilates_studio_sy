<?php
declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'date',
        'start_time',
        'end_time',
        'total_spots',
        'status',
    ];

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
            get: fn() => (int) Carbon::parse($this->start_time)
                ->diffInMinutes(Carbon::parse($this->end_time))
        );
    }

    protected function isPast(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    $endDateTime = Carbon::create(
                        $this->date?->year ?? null,
                        $this->date?->month ?? null,
                        $this->date?->day ?? null,
                        (int) explode(':', $this->end_time)[0],
                        (int) explode(':', $this->end_time)[1] ?? 0
                    );

                    return $endDateTime->isPast();
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    protected function isUpcoming(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    $startDateTime = Carbon::create(
                        $this->date?->year ?? null,
                        $this->date?->month ?? null,
                        $this->date?->day ?? null,
                        (int) explode(':', $this->start_time)[0],
                        (int) explode(':', $this->start_time)[1] ?? 0
                    );

                    return $startDateTime->isFuture();
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    protected function isAvailable(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->isScheduled() && !$this->isFull() && !$this->is_past
        );
    }

    protected function isWithinCancellationWindow(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    $startDateTime = Carbon::create(
                        $this->date?->year ?? null,
                        $this->date?->month ?? null,
                        $this->date?->day ?? null,
                        (int) explode(':', $this->start_time)[0],
                        (int) explode(':', $this->start_time)[1] ?? 0
                    );
                    $cutoff = $startDateTime->subHours(24);

                    return now()->greaterThanOrEqualTo($cutoff);
                } catch (\Exception) {
                    return true;
                }
            }
        );
    }

    protected function startsSoon(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    $startDateTime = Carbon::create(
                        $this->date?->year ?? null,
                        $this->date?->month ?? null,
                        $this->date?->day ?? null,
                        (int) explode(':', $this->start_time)[0],
                        (int) explode(':', $this->start_time)[1] ?? 0
                    );

                    return $startDateTime->between(now(), now()->addHour());
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    public function isBookableForUser(User $user): bool
    {
        return $this->is_available && $user->total_remaining_credits > 0;
    }

    public function isCancelableByUser(BookingSession $bookingSession): bool
    {
        return $bookingSession->isReserved()
            && !$this->is_within_cancellation_window
            && !$this->is_past;
    }

    public function getAvailableSpotsAttribute(): int
    {
        $reserved = $this->bookingSessions()
            ->where('status', BookingSessionStatusEnum::RESERVED->value)
            ->count();

        return max(0, $this->total_spots - $reserved);
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
