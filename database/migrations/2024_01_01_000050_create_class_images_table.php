<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnDelete();
            $table->string('url');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['class_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_images');
    }
};
