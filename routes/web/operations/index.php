<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\OperationsIndexAction;
use Illuminate\Support\Facades\Route;
Route::prefix('admin/operations')
    ->middleware(['web', 'auth', 'freeze.user', 'role.admin'])
    ->name('admin.operations.')
    ->group(function (): void {
        Route::get('/', OperationsIndexAction::class)->name('index');
        require __DIR__.'/packages.php';
        require __DIR__.'/store.php';
        require __DIR__.'/finance.php';
        require __DIR__.'/clients.php';
        require __DIR__.'/notifications.php';
        require __DIR__.'/bookings.php';
        require __DIR__.'/approvals.php';
    });
