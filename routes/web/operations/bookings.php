<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\{
    FreezeBookingAction,
    UnfreezeBookingAction,
    ProcessBookingRefundAction,
};
use Illuminate\Support\Facades\Route;
Route::prefix('bookings')->name('bookings.')->group(function (): void {
    Route::post('/{bookingId}/freeze', FreezeBookingAction::class)->name('freeze');
    Route::post('/{bookingId}/unfreeze', UnfreezeBookingAction::class)->name('unfreeze');
    Route::post('/{bookingId}/refund', ProcessBookingRefundAction::class)->name('refund');
});
