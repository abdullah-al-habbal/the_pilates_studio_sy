<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurrence_patterns', function (Blueprint $table) {
            $table->id();

            $table->string('name')
                ->unique()
                ->comment('Machine name: daily, weekly, etc.');
            $table->json('label');
            $table->unsignedSmallInteger('interval_days')
                ->comment('Number of days between occurrences: 1, 7, 14, 30');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('recurrence_patterns');
    }
};
