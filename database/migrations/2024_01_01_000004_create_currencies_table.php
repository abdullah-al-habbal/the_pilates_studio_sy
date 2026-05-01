<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('ISO 4217 e.g. USD, SYP');
            $table->json('name')->comment('Translatable display name');
            $table->string('symbol', 10)->comment('e.g. $, ¥');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 15, 4)->default(1.0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
