<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\{
    GetPendingExpensesAction,
    ApproveExpenseAction,
    RejectExpenseAction,
};
use Illuminate\Support\Facades\Route;
Route::prefix('approvals')->name('approvals.')->group(function (): void {
    Route::get('/pending', GetPendingExpensesAction::class)->name('pending');
    Route::post('/{expense}/approve', ApproveExpenseAction::class)->name('approve');
    Route::post('/{expense}/reject', RejectExpenseAction::class)->name('reject');
});
