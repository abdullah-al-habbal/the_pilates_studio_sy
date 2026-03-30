<?php

// filePath: routes\api\v1\public\auth.php

use App\Http\Controllers\Api\V1\Auth\{
    AuthController,
    EmailVerificationController
};
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])
        ->name('login');
    Route::post('email/verify', [EmailVerificationController::class, 'verify'])
        ->name('email.verify');
    Route::post('email/resend', [EmailVerificationController::class, 'resend'])
        ->name('email.resend');
});
