<?php
// database\migrations\2026_04_12_225805_create_center_merchandise_categories_table.php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('center_merchandise_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('Translatable: {en, ar}');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_merchandise_categories');
    }
};