<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->unsignedInteger('amount')->comment('Price in smallest currency unit');
            $table->timestamps();
            $table->unique(['package_id', 'currency_id'], 'unique_package_currency');
            $table->index('currency_id');
        });
    }
    public function down(): void { Schema::dropIfExists('package_prices'); }
};
