<?php
// filePath: routes/api/v1/index.php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware('api')->group(function () {
    Route::prefix('public')->name('public.')->group(function () {
        require_once __DIR__.'/public/auth.php';
        require_once __DIR__.'/public/pages.php';
        require_once __DIR__.'/public/app_settings.php';
        require_once __DIR__.'/public/classes.php';
        require_once __DIR__.'/public/class_sessions.php';
        require_once __DIR__.'/public/instructors.php';
        require_once __DIR__.'/public/languages.php';
        require_once __DIR__.'/public/mobile_app_version.php';
    });

    Route::middleware('auth:sanctum')->group(function () {
        require_once __DIR__.'/protected/auth.php';
        require_once __DIR__.'/protected/profiles.php';
        require_once __DIR__.'/protected/notifications.php';
        require_once __DIR__.'/protected/bookings.php';
        require_once __DIR__.'/protected/booking_sessions.php';
        require_once __DIR__.'/protected/packages.php';
        require_once __DIR__.'/protected/user_settings.php';
        require_once __DIR__.'/protected/languages.php';
    });
});
