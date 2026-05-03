<?php

declare(strict_types=1);

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete()
                ->comment('Booking to which this session reservation belongs');

            $table->foreignId('class_session_id')
                ->constrained('class_sessions')
                ->restrictOnDelete()
                ->comment('The specific class session being reserved');

            $table->string('status')
                ->default(BookingSessionStatusEnum::RESERVED->value)
                ->comment('Reservation lifecycle status: reserved or cancelled');

            $table->timestamp('cancelled_at')
                ->nullable()
                ->comment('Timestamp when the reservation was cancelled');

            $table->string('cancellation_reason')
                ->nullable()
                ->comment('Free-text or enum reason for cancellation');
            
            $table->unsignedTinyInteger('cancellation_type')
                ->nullable()
                ->comment('0=user_initiated, 1=admin_override, 2=system_expired');
            
            $table->foreignId('cancelled_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('attendance_status')
                ->default(AttendanceStatusEnum::MISSED->value)
                ->comment('Receptionist-set attendance: attended or missed');

            $table->timestamp('attended_at')
                ->nullable()
                ->comment('Timestamp when receptionist confirmed attendance');

            $table->timestamp('reminder_sent_at')
                ->nullable()
                ->comment('When the 24h reminder was sent (null = not sent yet)');

            $table->timestamps();

            $table->unique(['booking_id', 'class_session_id'], 'unique_booking_session')
                ->comment('Prevents double-booking the same session within a single booking');

            $table->index(['class_session_id', 'status'], 'idx_session_status');
            $table->index(['booking_id', 'status'], 'idx_booking_status');
            $table->index(['booking_id', 'class_session_id', 'status'], 'idx_booking_session_status');
            $table->index('attendance_status', 'idx_bs_attendance_status');
            $table->index('cancelled_by_user_id');
            $table->index('cancellation_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_sessions');
    }
};