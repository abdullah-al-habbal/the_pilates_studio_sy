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
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->unsignedSmallInteger('total_credits');
            $table->unsignedSmallInteger('remaining_credits');
            $table->string('status')->default(BookingStatusEnum::ACTIVE->value);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('paid_amount')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status'], 'idx_bookings_user_status');
            $table->index(['status', 'expires_at'], 'idx_bookings_status_expires');
            $table->index('currency_id');
        });
    }
    public function down(): void { Schema::dropIfExists('bookings'); }
};
