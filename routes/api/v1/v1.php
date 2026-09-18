<?php

// filePath: routes/api/v1/index.php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware('api')->group(function () {
    Route::prefix('public')->name('public.')->group(function () {
        require __DIR__ . '/public/auth.php';
        require __DIR__ . '/public/pages.php';
        require __DIR__ . '/public/app_settings.php';
        require __DIR__ . '/public/classes.php';
        require __DIR__ . '/public/class_sessions.php';
        require __DIR__ . '/public/instructors.php';
        require __DIR__ . '/public/languages.php';
        require __DIR__ . '/public/mobile_app_version.php';
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        require __DIR__ . '/admin/health.php';
    });

    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__ . '/protected/auth.php';
        require __DIR__ . '/protected/profiles.php';
        require __DIR__ . '/protected/notifications.php';
        require __DIR__ . '/protected/bookings.php';
        require __DIR__ . '/protected/booking_sessions.php';
        require __DIR__ . '/protected/packages.php';
        require __DIR__ . '/protected/user_settings.php';
        require __DIR__ . '/protected/languages.php';
    });
});
