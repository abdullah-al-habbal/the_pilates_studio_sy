<?php

// database/migrations/2026_04_12_225806_create_center_merchandise_images_table.php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_merchandise_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_merchandise_id')
                ->constrained('center_merchandises')
                ->cascadeOnDelete();
            $table->string('url');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('center_merchandise_id', 'idx_merch_images_merchandise');
            $table->index('is_primary', 'idx_merch_images_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_merchandise_images');
    }
};
