<?php

declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\GetStoreItemsAction;
use App\Http\Actions\Web\Admin\Operations\PlaceOrderAction;
use App\Http\Actions\Web\Admin\Operations\StoreWalkInOrderAction;
use Illuminate\Support\Facades\Route;

Route::prefix('store')->name('store.')->group(function (): void {
    Route::get('/items', GetStoreItemsAction::class)->name('index');
    Route::post('/orders', PlaceOrderAction::class)->name('order');
    Route::post('/walk-in-order', StoreWalkInOrderAction::class)->name('walk-in-order');
});
