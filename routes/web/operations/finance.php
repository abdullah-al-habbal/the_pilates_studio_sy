<?php
// routes/web/operations/finance.php

declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\GetDailyBalanceAction;
use App\Http\Actions\Web\Admin\Operations\GetExpenseBreakdownAction;
use App\Http\Actions\Web\Admin\Operations\GetExpenseCategoriesAction;
use App\Http\Actions\Web\Admin\Operations\RecordExpenseAction;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->name('finance.')->group(function (): void {
    Route::get('/daily', GetDailyBalanceAction::class)->name('daily');
    Route::get('/categories', GetExpenseCategoriesAction::class)->name('categories');
    Route::get('/expenses/breakdown', GetExpenseBreakdownAction::class)->name('expenses.breakdown');
    Route::post('/expenses', RecordExpenseAction::class)->name('expenses');
});
