<?php

namespace Database\Factories;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingSessionFactory extends Factory
{
    protected $model = BookingSession::class;

    public function definition(): array
    {
        return [
            'booking_id'       => Booking::where('status', BookingStatusEnum::ACTIVE->value)
                ->inRandomOrder()->first()?->id ?? Booking::factory(),
            'class_session_id' => ClassSession::where('status', ClassSessionStatusEnum::SCHEDULED->value)
                ->inRandomOrder()->first()?->id ?? ClassSession::factory(),
            'status'           => BookingSessionStatusEnum::RESERVED->value,
            'cancelled_at'     => null,
        ];
    }

    public function attended(): static
    {
        return $this->state(fn () => [
            'attendance_status' => AttendanceStatusEnum::ATTENDED,
            'attended_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status'       => BookingSessionStatusEnum::CANCELLED,
            'cancelled_at' => now()->subHours(rand(1, 48)),
        ]);
    }

    public function missed(): static
    {
        return $this->state(fn () => [
            'attendance_status' => AttendanceStatusEnum::MISSED,
        ]);
    }
}
