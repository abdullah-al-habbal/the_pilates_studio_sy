<?php
// filePath: app/Models/BookingSession.php

namespace App\Models;

use App\Enums\BookingSessionStatusEnum;
use App\Observers\BookingSessionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy(BookingSessionObserver::class)]
class BookingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'class_session_id',
        'status',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'status'       => BookingSessionStatusEnum::class,
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
        return $this->status === BookingSessionStatusEnum::ATTENDED;
    }

    public function isNoShow(): bool
    {
        return $this->status === BookingSessionStatusEnum::NO_SHOW;
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
