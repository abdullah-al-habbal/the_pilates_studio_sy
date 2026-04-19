<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'class_session_id',
        'status',
        'cancelled_at',
        'attendance_status',
        'attended_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'attended_at' => 'datetime',
            'status' => BookingSessionStatusEnum::class,
            'attendance_status' => AttendanceStatusEnum::class,
        ];
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingSessionStatusEnum::CANCELLED;
    }

    public function isReserved(): bool
    {
        return $this->status === BookingSessionStatusEnum::RESERVED;
    }

    public function isAttended(): bool
    {
        return $this->attendance_status === AttendanceStatusEnum::ATTENDED;
    }

    public function isMissed(): bool
    {
        return $this->attendance_status === AttendanceStatusEnum::MISSED;
    }

    public function markAttended(): void
    {
        $this->update([
            'attendance_status' => AttendanceStatusEnum::ATTENDED,
            'attended_at' => now(),
        ]);
    }

    public function markMissed(): void
    {
        $this->update([
            'attendance_status' => AttendanceStatusEnum::MISSED,
            'attended_at' => null,
        ]);
    }

    protected function canCancel(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    if (!$this->isReserved() || !$this->classSession) {
                        return false;
                    }
                    return !$this->classSession->is_within_cancellation_window
                        && !$this->classSession->is_past;
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    protected function canMarkAttended(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    if (!$this->isReserved() || !$this->classSession) {
                        return false;
                    }
                    $startDateTime = Carbon::create(
                        $this->classSession->date?->year ?? null,
                        $this->classSession->date?->month ?? null,
                        $this->classSession->date?->day ?? null,
                        (int) explode(':', $this->classSession->start_time)[0],
                        (int) explode(':', $this->classSession->start_time)[1] ?? 0
                    );

                    return $startDateTime->isPast();
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    protected function canMarkMissed(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    if (!$this->isReserved() || !$this->classSession) {
                        return false;
                    }
                    $endDateTime = Carbon::create(
                        $this->classSession->date?->year ?? null,
                        $this->classSession->date?->month ?? null,
                        $this->classSession->date?->day ?? null,
                        (int) explode(':', $this->classSession->end_time)[0],
                        (int) explode(':', $this->classSession->end_time)[1] ?? 0
                    );

                    return $endDateTime->isPast();
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    protected function isRefundable(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->can_cancel
        );
    }

    protected function attendanceRequired(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    if (!$this->classSession) {
                        return false;
                    }

                    return $this->is_reserved && $this->classSession->starts_soon;
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }
}
