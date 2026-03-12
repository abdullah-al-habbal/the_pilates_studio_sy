<?php

declare(strict_types=1);

use App\Enums\ClassSessionStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnDelete()
                ->comment('Reference to the parent class');

            $table->date('date')
                ->comment('Session date');

            $table->time('start_time')
                ->comment('Session start time');

            $table->time('end_time')
                ->comment('Session end time');

            $table->unsignedSmallInteger('total_spots')
                ->comment('Number of available spots copied from the class at generation time');

            $table->string('status')
                ->default(ClassSessionStatusEnum::SCHEDULED->value)
                ->comment('Session status: scheduled, completed, or cancelled');

            $table->timestamps();

            $table->softDeletes()
                ->comment('Soft deletion timestamp');

            $table->unique(['class_id', 'date', 'start_time'], 'unique_class_session')
                ->comment('A class cannot have two sessions at the same date and start time');

            $table->index(['date', 'status'], 'idx_date_status');
            $table->index('class_id', 'idx_class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
