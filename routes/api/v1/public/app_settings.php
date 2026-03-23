<?php
// filePath: routes/api/v1/public/app_settings.php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppSetting\AppSettingController;
use Illuminate\Support\Facades\Route;

Route::get('app-settings/{key}', [AppSettingController::class, 'showByKey'])
    ->name('app-settings.show');
