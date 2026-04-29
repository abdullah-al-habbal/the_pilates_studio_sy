<?php
declare(strict_types=1);
use App\Enums\UserStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('phone_number')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->date('date_of_birth')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('status')->default(UserStatusEnum::ACTIVE->value)
                ->comment('active | frozen | deactivated');
            $table->timestamp('frozen_at')->nullable();
            $table->string('freeze_reason')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('frozen_at');
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
