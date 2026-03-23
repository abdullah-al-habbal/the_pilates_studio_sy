<?php
// filePath: routes/api/v1/protected/user_settings.php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\UserSetting\UserSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('user-settings')->name('user-settings.')->group(function () {
    Route::get('/', [UserSettingController::class, 'show'])->name('show');
    Route::patch('/', [UserSettingController::class, 'update'])->name('update');
});
