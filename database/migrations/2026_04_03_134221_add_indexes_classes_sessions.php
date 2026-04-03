<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->index(['status', 'start_date', 'end_date'], 'idx_classes_status_dates');
            $table->index('start_time', 'idx_classes_start_time');
            $table->index('class_category_id', 'idx_classes_category');
            $table->index('instructor_id', 'idx_classes_instructor');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->index(['date', 'start_time'], 'idx_sessions_date_time');
            $table->index('class_id', 'idx_sessions_class');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex('idx_classes_status_dates');
            $table->dropIndex('idx_classes_start_time');
            $table->dropIndex('idx_classes_category');
            $table->dropIndex('idx_classes_instructor');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_date_time');
            $table->dropIndex('idx_sessions_class');
        });
    }
};
