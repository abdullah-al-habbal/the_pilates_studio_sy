<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds weekday-based scheduling as a second recurrence mode.
     *
     * A class is scheduled EITHER by a RecurrencePattern (fixed interval_days
     * stride) OR by an explicit set of weekdays. Exactly one of the two must be
     * set — enforced in ClassScheduleValidationService, not by the database,
     * since MySQL has no usable CHECK across a nullable FK and a JSON column.
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->json('weekdays')
                ->nullable()
                ->after('recurrence_pattern_id')
                ->comment('Weekday-mode schedule, e.g. ["sunday","wednesday"]. Null when using recurrence_pattern_id.');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('recurrence_pattern_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('weekdays');
        });

        // Interval mode becomes mandatory again, so any weekday-only class must
        // be pointed at a pattern first or this will fail — which is intended.
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('recurrence_pattern_id')
                ->nullable(false)
                ->change();
        });
    }
};
