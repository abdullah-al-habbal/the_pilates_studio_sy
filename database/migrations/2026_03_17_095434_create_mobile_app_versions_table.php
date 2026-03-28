<?php
// filePath: database\migrations\2026_03_17_095434_create_mobile_app_versions_table.php

declare(strict_types=1);

use App\Enums\AppNameEnum;
use App\Enums\MobilePlatformEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mobile_app_versions', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->string('app_name', 50);
            $table->string('platform', 20);

            $table->string('min_version', 20);
            $table->string('latest_version', 20);

            $table->text('force_message')->nullable();
            $table->string('store_url', 255)->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['app_name', 'platform'], 'mobile_versions_app_platform_unique');
            $table->index(['platform', 'active'], 'mobile_versions_platform_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_versions');
    }
};
