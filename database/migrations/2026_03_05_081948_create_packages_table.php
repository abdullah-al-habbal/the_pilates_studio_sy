<?php

declare(strict_types=1);

use App\Enums\PackageTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->unsignedSmallInteger('total_credits');
            $table->unsignedInteger('price')->default(0)->comment('Legacy SYP base price');
            $table->boolean('is_active')->default(true);
            $table->string('type')->default(PackageTypeEnum::STANDARD->value)
                ->comment('by_system | for_freeze_client');
            $table->string('generated_reason')->nullable()
                ->comment('Populated when type != standard');
            $table->unsignedSmallInteger('validity_days')->nullable()
                ->comment('If set, booking expires_at = created_at + validity_days');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};