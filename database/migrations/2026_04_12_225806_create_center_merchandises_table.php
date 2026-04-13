<?php
// database\migrations\2026_04_12_225806_create_center_merchandises_table.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('center_merchandises', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('Translatable: {en, ar}');
            $table->json('description')->nullable()->comment('Translatable: {en, ar}');
            $table->unsignedInteger('price')->default(0)->comment('Price in SYP, stored as integer');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('center_merchandise_categories')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('category_id', 'idx_merchandise_category');
            $table->index(['stock_quantity', 'price'], 'idx_merchandise_stock_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_merchandises');
    }
};