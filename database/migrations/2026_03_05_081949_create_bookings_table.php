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
                ->comment('Booking status: active, exhausted, expired, or cancelled');

            $table->timestamp('expires_at')
                ->nullable()
                ->comment('Expiration timestamp based on package validity');

            $table->timestamps();

            $table->softDeletes()
                ->comment('Soft deletion timestamp');

            $table->index(['user_id', 'status'], 'idx_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
