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
        DB::table('class_images')
            ->where('is_primary', true)
            ->select('class_id')
            ->groupBy('class_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('class_id')
            ->each(function (int $classId): void {
                $duplicateIds = DB::table('class_images')
                    ->where('class_id', $classId)
                    ->where('is_primary', true)
                    ->orderBy('id')
                    ->pluck('id')
                    ->slice(1)
                    ->all();

                if ($duplicateIds !== []) {
                    DB::table('class_images')
                        ->whereIn('id', $duplicateIds)
                        ->update(['is_primary' => false]);
                }
            });

        $usesSqlite = DB::connection()->getDriverName() === 'sqlite';

        Schema::table('class_images', function (Blueprint $table) use ($usesSqlite): void {
            $column = $table->unsignedBigInteger('primary_class_id')
                ->nullable()
                ->comment('Generated key enforcing at most one primary image per class');

            $usesSqlite
                ? $column->virtualAs('CASE WHEN is_primary = 1 THEN class_id ELSE NULL END')
                : $column->storedAs('CASE WHEN is_primary = 1 THEN class_id ELSE NULL END');

            $table->unique('primary_class_id', 'class_images_one_primary_per_class');
        });
    }

    public function down(): void
    {
        Schema::table('class_images', function (Blueprint $table): void {
            $table->dropUnique('class_images_one_primary_per_class');
            $table->dropColumn('primary_class_id');
        });
    }
};
