<?php

// filePath: routes/api/v1/protected/bookings.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Booking\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('{id}', [BookingController::class, 'show'])->name('show');
});
