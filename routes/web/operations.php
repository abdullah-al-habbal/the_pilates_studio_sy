<?php
// routes\web\operations.php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\Operations\OperationsIndexAction;
use App\Http\Actions\Web\Admin\Operations\GetPackagesAction;
use App\Http\Actions\Web\Admin\Operations\AssignPackageAction;
use App\Http\Actions\Web\Admin\Operations\GetStoreItemsAction;
use App\Http\Actions\Web\Admin\Operations\PlaceOrderAction;
use App\Http\Actions\Web\Admin\Operations\GetDailyBalanceAction;
use App\Http\Actions\Web\Admin\Operations\RecordExpenseAction;
use App\Http\Actions\Web\Admin\Operations\GetClientsAction;
use App\Http\Actions\Web\Admin\Operations\FreezeBookingAction;
use App\Http\Actions\Web\Admin\Operations\UnfreezeBookingAction;
use App\Http\Actions\Web\Admin\Client\ClientDetailsAction;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/operations')
    ->middleware(['web', 'auth'])
    ->name('admin.operations.')
    ->group(function (): void {
        Route::get('/', OperationsIndexAction::class)->name('index');

        Route::get('/packages', GetPackagesAction::class)->name('packages.index');
        Route::post('/packages/{packageId}/assign', AssignPackageAction::class)->name('packages.assign');

        Route::get('/store/items', GetStoreItemsAction::class)->name('store.index');
        Route::post('/store/orders', PlaceOrderAction::class)->name('store.order');

        Route::get('/finance/daily', GetDailyBalanceAction::class)->name('finance.daily');
        Route::post('/finance/expenses', RecordExpenseAction::class)->name('finance.expenses');

        Route::get('/clients', GetClientsAction::class)->name('clients.index');
        Route::get('/clients/{userId}/details', ClientDetailsAction::class)->name('clients.details');

        Route::post('/bookings/{bookingId}/freeze', FreezeBookingAction::class)->name('bookings.freeze');
        Route::post('/bookings/{bookingId}/unfreeze', UnfreezeBookingAction::class)->name('bookings.unfreeze');
    });
