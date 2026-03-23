<?php
// filePath: routes/api/v1/public/mobile_app_version.php

declare(strict_types=1);

use App\Actions\V1\MobileAppVersion\GetCompatibility\GetCompatibilityAction;
use Illuminate\Support\Facades\Route;

Route::prefix('app-version')->name('app_version.')->group(function () {
    Route::get('compatibility', GetCompatibilityAction::class)
        ->name('compatibility');
});
