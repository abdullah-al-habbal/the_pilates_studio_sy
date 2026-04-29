<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('phone_number');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->date('date_of_birth')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('status')->default(UserStatusEnum::ACTIVE->value)
                ->comment('active | frozen | deactivated');
            $table->timestamp('frozen_at')->nullable()
                ->comment('When the user was frozen');
            $table->string('freeze_reason')->nullable()
                ->comment('Admin note for freeze action');
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('is_active')
                ->storedAs('IF(deleted_at IS NULL, 1, 0)');

            $table->unique(['email', 'is_active'], 'users_email_active_unique');
            $table->unique(['phone_number', 'is_active'], 'users_phone_active_unique');
        });


        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
