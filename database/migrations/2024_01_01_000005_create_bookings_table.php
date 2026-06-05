<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('User who purchased the package');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who created this booking');
            $table->index('created_by');

            $table->foreignId('package_id')
                ->constrained('packages')
                ->restrictOnDelete()
                ->comment('Purchased package associated with this booking');

            $table->unsignedSmallInteger('total_credits')
                ->comment('Snapshot of package credits at purchase time');

            $table->unsignedSmallInteger('remaining_credits')
                ->comment('Credits remaining; decremented per session attendance');

            $table->string('status')
                ->default(BookingStatusEnum::ACTIVE->value)
                ->comment('Booking status: active, exhausted, expired, frozen, or cancelled');

            $table->timestamp('expires_at')
                ->nullable()
                ->comment('Expiration timestamp based on package validity');

            $table->unsignedInteger('paid_amount')->nullable();
            
            $table->foreignId('currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete();
            
            $table->decimal('exchange_rate_snapshot', 12, 6)
                ->nullable()
                ->comment('Rate at purchase time relative to base currency');
            
            $table->string('source_type')
                ->default('standard')
                ->comment('standard | freeze_origin | freeze_resume');
            
            $table->unsignedBigInteger('parent_booking_id')
                ->nullable()
                ->comment('Points to the original booking when source_type=freeze_resume');
            
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('unfrozen_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['user_id', 'status', 'remaining_credits'], 'idx_user_status_credits');
            $table->index(['status', 'expires_at'], 'idx_bookings_status_expires');
            $table->index('currency_id');
            $table->index('parent_booking_id');
            
            $table->foreign('parent_booking_id')
                ->references('id')
                ->on('bookings')
                ->nullOnDelete();

            $table->unsignedBigInteger('active_user_id')
                ->nullable()
                ->storedAs(
                    "CASE WHEN status = '" . BookingStatusEnum::ACTIVE->value . 
                    "' AND remaining_credits > 0 THEN user_id ELSE NULL END"
                )
                ->comment('Used to enforce unique active booking per user');

            $table->unique(['active_user_id'], 'unique_active_booking_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};