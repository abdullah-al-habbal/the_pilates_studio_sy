<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();

            $table->morphs('notifiable');

            $table->string('notification_class')->index();

            $table->json('data');

            $table->string('channel')
                ->default('fcm')
                ->index();

            $table->timestamp('sent_at')
                ->nullable()
                ->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};