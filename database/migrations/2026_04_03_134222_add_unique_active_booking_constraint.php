<?php

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('active_user_id')
                ->nullable()
                ->storedAs("CASE WHEN status = '" . BookingStatusEnum::ACTIVE->value . "' AND remaining_credits > 0 THEN user_id ELSE NULL END")
                ->comment('Used to enforce unique active booking per user');
            
            $table->unique(['active_user_id'], 'unique_active_booking_per_user');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('unique_active_booking_per_user');
            $table->dropColumn('active_user_id');
        });
    }
};
