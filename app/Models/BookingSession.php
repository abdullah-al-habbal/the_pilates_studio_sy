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
                if (! $this->isReserved() || ! $this->classSession) {
                    return false;
                }

                return ! $this->classSession->is_within_cancellation_window
                    && ! $this->classSession->is_past;
            }
        );
    }

    protected function canMarkAttended(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->isReserved() || ! $this->classSession) {
                    return false;
                }

                return Carbon::parse("{$this->classSession->date} {$this->classSession->start_time}")->isPast();
            }
        );
    }

    protected function canMarkMissed(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->isReserved() || ! $this->classSession) {
                    return false;
                }

                return Carbon::parse("{$this->classSession->date} {$this->classSession->end_time}")->isPast();
            }
        );
    }

    protected function isRefundable(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->can_cancel
        );
    }

    protected function attendanceRequired(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->classSession) {
                    return false;
                }

                return $this->is_reserved && $this->classSession->starts_soon;
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
