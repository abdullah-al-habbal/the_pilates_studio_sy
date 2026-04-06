<?php

declare(strict_types=1);

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
                ->comment('Reservation status: reserved, attended, cancelled, or no_show');

            $table->timestamp('cancelled_at')
                ->nullable()
                ->comment('Timestamp when the reservation was cancelled');

            $table->timestamps();

            $table->unique(['booking_id', 'class_session_id'], 'unique_booking_session')
                ->comment('Prevents double-booking the same session within a single booking');

            $table->index(['class_session_id', 'status'], 'idx_session_status');
            $table->index(['booking_id', 'status'], 'idx_booking_status');
            $table->index(['booking_id', 'class_session_id', 'status'], 'idx_booking_session_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_sessions');
    }
};
