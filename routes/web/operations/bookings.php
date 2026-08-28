<?php

declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\FreezeBookingAction;
use App\Http\Actions\Web\Admin\Operations\GetBackfillSessionsAction;
use App\Http\Actions\Web\Admin\Operations\ProcessBookingRefundAction;
use App\Http\Actions\Web\Admin\Operations\UnfreezeBookingAction;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function (): void {
    // Declared before /{bookingId}/* so "backfill" is never captured as a booking id.
    Route::get('/backfill/sessions', GetBackfillSessionsAction::class)->name('backfill.sessions');

    Route::post('/{bookingId}/freeze', FreezeBookingAction::class)->name('freeze');
    Route::post('/{bookingId}/unfreeze', UnfreezeBookingAction::class)->name('unfreeze');
    Route::post('/{bookingId}/refund', ProcessBookingRefundAction::class)->name('refund');
});
