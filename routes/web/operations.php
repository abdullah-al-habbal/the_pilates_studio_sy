<?php
// routes\web\operations.php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\Operations\{
    GetExpenseCategoriesAction,
    OperationsIndexAction,
    GetPackagesAction,
    AssignPackageAction,
    GetStoreItemsAction,
    PlaceOrderAction,
    GetDailyBalanceAction,
    GetExpenseBreakdownAction,
    ProcessBookingRefundAction,
    RecordExpenseAction,
    GetClientsAction,
    FreezeBookingAction,
    UnfreezeBookingAction,
    StoreWalkInOrderAction,
    CreatePackageAction,
    UpdatePackageAction,
    DeletePackageAction,
    SendPushNotificationAction,
};
use App\Http\Actions\Web\Admin\Client\ClientDetailsAction;
use Illuminate\Support\Facades\Route;

use App\Http\Actions\Web\Admin\Operations\ApproveExpenseAction;
use App\Http\Actions\Web\Admin\Operations\GetPendingExpensesAction;
use App\Http\Actions\Web\Admin\Operations\RejectExpenseAction;

Route::prefix('admin/operations')
    ->middleware(['web', 'auth', 'freeze.user', 'role.admin'])
    ->name('admin.operations.')
    ->group(function (): void {
        Route::get('/', OperationsIndexAction::class)->name('index');

        Route::get('/packages', GetPackagesAction::class)->name('packages.index');
        Route::post('/packages', CreatePackageAction::class)->name('packages.store');
        Route::put('/packages/{package}', UpdatePackageAction::class)->name('packages.update');
        Route::delete('/packages/{package}', DeletePackageAction::class)->name('packages.destroy');
        Route::post('/packages/{packageId}/assign', AssignPackageAction::class)->name('packages.assign');

        Route::get('/store/items', GetStoreItemsAction::class)->name('store.index');
        Route::post('/store/orders', PlaceOrderAction::class)->name('store.order');
        Route::post('/store/walk-in-order', StoreWalkInOrderAction::class)->name('store.walk-in-order');

        Route::get('/finance/daily', GetDailyBalanceAction::class)->name('finance.daily');
        Route::get('/finance/categories', GetExpenseCategoriesAction::class)->name('finance.categories');
        Route::get('/finance/expenses/breakdown', GetExpenseBreakdownAction::class)->name('finance.expenses.breakdown');
        Route::post('/finance/expenses', RecordExpenseAction::class)->name('finance.expenses');

        Route::get('/clients', GetClientsAction::class)->name('clients.index');
        Route::get('/clients/{userId}/details', ClientDetailsAction::class)->name('clients.details');

        Route::post('/notifications/send', SendPushNotificationAction::class)->name('notifications.send');

        Route::post('/bookings/{bookingId}/freeze', FreezeBookingAction::class)->name('bookings.freeze');
        Route::post('/bookings/{bookingId}/unfreeze', UnfreezeBookingAction::class)->name('bookings.unfreeze');
        Route::post('/bookings/{bookingId}/refund', ProcessBookingRefundAction::class)->name('bookings.refund');

        Route::prefix('approvals')->group(function (): void {
            Route::get('/pending', GetPendingExpensesAction::class)->name('approvals.pending');
            Route::post('/{expense}/approve', ApproveExpenseAction::class)->name('approvals.approve');
            Route::post('/{expense}/reject', RejectExpenseAction::class)->name('approvals.reject');
        });
    });
