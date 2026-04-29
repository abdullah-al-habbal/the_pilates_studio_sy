<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (!Schema::hasColumn('bookings', 'source_type')) {
                $table->string('source_type')->default('standard')
                      ->comment('standard | freeze_origin | freeze_resume');
            }
            if (!Schema::hasColumn('bookings', 'parent_booking_id')) {
                $table->unsignedBigInteger('parent_booking_id')->nullable()
                      ->comment('Points to the original booking when source_type=freeze_resume');
                $table->foreign('parent_booking_id')->references('id')->on('bookings')->nullOnDelete();
                $table->index('parent_booking_id');
            }
            if (!Schema::hasColumn('bookings', 'frozen_at')) {
                $table->timestamp('frozen_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'unfrozen_at')) {
                $table->timestamp('unfrozen_at')->nullable();
            }
        });

        Schema::table('booking_sessions', function (Blueprint $table): void {
            if (!Schema::hasColumn('booking_sessions', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()
                      ->comment('Free-text or enum reason for cancellation');
            }
            if (!Schema::hasColumn('booking_sessions', 'cancellation_type')) {
                $table->unsignedTinyInteger('cancellation_type')->nullable()
                      ->comment('0=user_initiated, 1=admin_override, 2=system_expired');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign(['parent_booking_id']);
            $table->dropColumn(['source_type', 'parent_booking_id', 'frozen_at', 'unfrozen_at']);
        });

        Schema::table('booking_sessions', function (Blueprint $table): void {
            $table->dropColumn(['cancellation_reason', 'cancellation_type']);
        });
    }
};
