<?php

// filePath: routes/api.php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppSetting\AppSettingController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Booking\BookingController;
use App\Http\Controllers\Api\V1\BookingSession\BookingSessionController;
use App\Http\Controllers\Api\V1\Classes\ClassesController;
use App\Http\Controllers\Api\V1\ClassSession\ClassSessionController;
use App\Http\Controllers\Api\V1\Instructor\InstructorController;
use App\Http\Controllers\Api\V1\Language\LanguageController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Package\PackageController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Setting\SettingController;
use App\Http\Controllers\Api\V1\StaticPage\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login');
        Route::post('email/verify', [EmailVerificationController::class, 'verify'])
            ->middleware('throttle:10,1')
            ->name('email.verify');
        Route::post('email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:3,1')
            ->name('email.resend');
    });

    Route::get('pages/{slug}', [StaticPageController::class, 'showBySlug'])->name('pages.show');
    Route::get('app-settings/{key}', [AppSettingController::class, 'showByKey'])->name('app-settings.show');

    Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');

    Route::get('classes', [ClassesController::class, 'index'])->name('classes.index');
    Route::get('classes/{id}', [ClassesController::class, 'show'])->name('classes.show');
    Route::get('class-sessions', [ClassSessionController::class, 'index'])->name('class-sessions.index');
    Route::get('class-sessions/{id}', [ClassSessionController::class, 'show'])->name('class-sessions.show');
    Route::get('instructors/{id}', [InstructorController::class, 'show'])->name('instructors.show');

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::prefix('profiles')->name('profiles.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('{id}', [NotificationController::class, 'show'])->name('show');
            Route::patch('{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::patch('bulk/read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-read');
        });

        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [BookingController::class, 'index'])->name('index');
            Route::get('{id}', [BookingController::class, 'show'])->name('show');
        });

        Route::prefix('booking-sessions')->name('booking-sessions.')->group(function () {
            Route::get('/', [BookingSessionController::class, 'index'])->name('index');
            Route::get('{id}', [BookingSessionController::class, 'show'])->name('show');
        });

        Route::get('packages/{id}', [PackageController::class, 'show'])->name('packages.show');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'show'])->name('show');
            Route::patch('/', [SettingController::class, 'update'])->name('update');
        });

        Route::post('languages/set-locale', [LanguageController::class, 'setLocale'])->name('languages.set-locale');
    });
});
