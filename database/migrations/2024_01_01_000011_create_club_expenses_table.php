<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('club_expense_categories')->restrictOnDelete();
            
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();
                
            $table->unsignedInteger('amount');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->after('notes')
                ->comment('pending | approved | rejected');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->index('status');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('expense_date');
            $table->timestamps();
            $table->index('expense_date');
            $table->index('category_id');
            $table->index('recorded_by');
            $table->index('currency_id');
        });
    }
    public function down(): void { Schema::dropIfExists('club_expenses'); }
};
