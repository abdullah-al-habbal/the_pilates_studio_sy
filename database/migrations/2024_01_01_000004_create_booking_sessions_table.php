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
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained('class_sessions')->restrictOnDelete();
            $table->string('status')->default(BookingSessionStatusEnum::RESERVED->value);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attendance_status')->default(AttendanceStatusEnum::MISSED->value);
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
            $table->unique(['booking_id', 'class_session_id'], 'unique_booking_session');
            $table->index(['class_session_id', 'status'], 'idx_session_status');
            $table->index(['booking_id', 'status'], 'idx_booking_status');
            $table->index('attendance_status');
            $table->index('cancelled_by_user_id');
        });
    }
    public function down(): void { Schema::dropIfExists('booking_sessions'); }
};
