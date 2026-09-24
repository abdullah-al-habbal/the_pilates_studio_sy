<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurrence_patterns', function (Blueprint $table): void {
            $table->string('frequency_unit')
                ->nullable()
                ->after('interval_days')
                ->comment('Calendar-aware recurrence unit: day, week, or month.');

            $table->unsignedSmallInteger('frequency_interval')
                ->nullable()
                ->after('frequency_unit')
                ->comment('Positive number of frequency units between occurrences.');
        });

        DB::table('recurrence_patterns')
            ->select(['id', 'name', 'interval_days'])
            ->orderBy('id')
            ->each(function (object $pattern): void {
                [$unit, $interval] = match ($pattern->name) {
                    'daily' => ['day', 1],
                    'weekly' => ['week', 1],
                    'biweekly' => ['week', 2],
                    'monthly' => ['month', 1],
                    default => ['day', max(1, (int) $pattern->interval_days)],
                };

                DB::table('recurrence_patterns')
                    ->where('id', $pattern->id)
                    ->update([
                        'frequency_unit' => $unit,
                        'frequency_interval' => $interval,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('recurrence_patterns', function (Blueprint $table): void {
            $table->dropColumn(['frequency_unit', 'frequency_interval']);
        });
    }
};
