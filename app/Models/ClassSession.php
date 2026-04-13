<?php

namespace App\Models;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
