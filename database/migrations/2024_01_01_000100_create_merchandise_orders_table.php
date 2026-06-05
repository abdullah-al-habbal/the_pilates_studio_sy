<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchandise_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_id')
                ->constrained('center_merchandises')
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who created this order');
            $table->index('created_by');
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->unsignedInteger('paid_amount')->nullable();
            $table->decimal('exchange_rate_snapshot', 12, 6)
                ->nullable()
                ->comment('Rate at purchase time relative to base currency');

            $table->json('merchandise_name_snapshot')
                ->nullable()
                ->comment('Snapshot of merchandise name at order time');

            $table->unsignedInteger('merchandise_unit_price_snapshot')
                ->nullable()
                ->comment('Snapshot of unit price in order currency at order time');

            $table->index('merchandise_id', 'idx_order_merchandise');
            $table->index('customer_id', 'idx_order_customer');
            $table->index('ordered_at', 'idx_order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_orders');
    }
};
