<?php

declare(strict_types=1);

use App\Enums\ClassStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')
                ->constrained('instructors')
                ->restrictOnDelete();

            $table->foreignId('class_category_id')
                ->constrained('class_categories')
                ->restrictOnDelete();

            $table->foreignId('recurrence_pattern_id')
                ->nullable()
                ->constrained('recurrence_patterns')
                ->nullOnDelete();

            $table->json('title');
            $table->json('about')->nullable();

            $table->time('start_time');
            $table->time('end_time');

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->unsignedSmallInteger('total_spots');

            $table->string('status')
                ->default(ClassStatusEnum::ACTIVE->value);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);

            $table->index(['status', 'start_date', 'end_date'], 'idx_classes_status_dates');
            $table->index('start_time', 'idx_classes_start_time');
            $table->index('class_category_id', 'idx_classes_category');
            $table->index('instructor_id', 'idx_classes_instructor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
