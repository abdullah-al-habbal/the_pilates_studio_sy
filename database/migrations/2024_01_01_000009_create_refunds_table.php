<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->morphs('refundable')->comment('bookings or merchandise_orders');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->text('reason')->nullable();
            $table->foreignId('refunded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('refunded_at');
            $table->timestamps();
            $table->index(['refundable_type', 'refundable_id'], 'idx_refundable');
            $table->index('user_id');
            $table->index('refunded_at');
            $table->index('refunded_by');
        });
    }
    public function down(): void { Schema::dropIfExists('refunds'); }
};
