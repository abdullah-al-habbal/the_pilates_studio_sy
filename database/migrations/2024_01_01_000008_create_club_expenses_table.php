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
            $table->unsignedInteger('amount');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('expense_date');
            $table->timestamps();
            $table->index('expense_date');
            $table->index('category_id');
            $table->index('recorded_by');
        });
    }
    public function down(): void { Schema::dropIfExists('club_expenses'); }
};
