<?php
// database\migrations\2026_04_12_225806_create_center_merchandise_images_table.php
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
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Optional: the member who purchased. Null if staff/owner takes the item.');
            $table->timestamp('ordered_at');
            $table->timestamps();
            $table->index('ordered_at', 'idx_merch_orders_date');
            $table->index('merchandise_id', 'idx_merch_orders_merchandise');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_orders');
    }
};