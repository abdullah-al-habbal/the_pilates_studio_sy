<?php

// routes/web/operations/bookings.php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\BatchUpdateBookingSessionsAction;
use App\Http\Actions\Web\Admin\Operations\CancelBookingSessionAction;
use App\Http\Actions\Web\Admin\Operations\FreezeBookingAction;
use App\Http\Actions\Web\Admin\Operations\GetBackfillSessionsAction;
use App\Http\Actions\Web\Admin\Operations\ListBookingSessionsAction;
use App\Http\Actions\Web\Admin\Operations\ProcessBookingRefundAction;
use App\Http\Actions\Web\Admin\Operations\UnfreezeBookingAction;
use App\Http\Actions\Web\Admin\Operations\UpdateBookingAction;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function (): void {
    Route::get('/backfill/sessions', GetBackfillSessionsAction::class)->name('backfill.sessions');

    Route::post('/sessions/{bookingSessionId}/cancel', CancelBookingSessionAction::class)->name('sessions.cancel');

    Route::get('/{bookingId}/sessions', ListBookingSessionsAction::class)->name('sessions.index');
    Route::post('/{bookingId}/sessions/batch', BatchUpdateBookingSessionsAction::class)->name('sessions.batch');

    Route::put('/{bookingId}', UpdateBookingAction::class)->name('update');
    Route::post('/{bookingId}/freeze', FreezeBookingAction::class)->name('freeze');
    Route::post('/{bookingId}/unfreeze', UnfreezeBookingAction::class)->name('unfreeze');
    Route::post('/{bookingId}/refund', ProcessBookingRefundAction::class)->name('refund');
});
