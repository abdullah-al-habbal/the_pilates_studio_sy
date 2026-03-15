<?php

// filePath: routes/api/v1/protected/booking_sessions.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\BookingSession\BookingSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('booking-sessions')->name('booking-sessions.')->group(function () {
    Route::get('/', [BookingSessionController::class, 'index'])->name('index');
    Route::get('{id}', [BookingSessionController::class, 'show'])->name('show');
});
