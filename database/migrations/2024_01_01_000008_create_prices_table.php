<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable');
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->unsignedInteger('amount')->comment('Price in smallest currency unit');
            $table->timestamps();

            $table->unique(['priceable_type', 'priceable_id', 'currency_id'], 'unique_price_morph_currency');
            $table->index('currency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
