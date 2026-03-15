<?php

// filePath: routes/api/v1/protected/settings.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Setting\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'show'])->name('show');
    Route::patch('/', [SettingController::class, 'update'])->name('update');
});
