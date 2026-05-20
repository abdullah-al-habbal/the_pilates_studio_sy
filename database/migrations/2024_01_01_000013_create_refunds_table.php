<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->morphs('refundable');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->unsignedInteger('amount');
            $table->decimal('exchange_rate_snapshot', 12, 6)
                ->nullable()
                ->comment('Rate at refund time relative to base currency');
            $table->text('reason')->nullable();
            $table->foreignId('refunded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('refunded_at');
            $table->timestamps();
            $table->index(['refundable_type', 'refundable_id'], 'idx_refundable');
            $table->index('user_id');
            $table->index('refunded_at');
            $table->index('refunded_by');
            $table->index('currency_id');
        });
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE refunds MODIFY refundable_type VARCHAR(255) COMMENT 'bookings or merchandise_orders'");
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
