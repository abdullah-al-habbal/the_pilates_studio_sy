<?php

// filePath: routes/api/v1/protected/languages.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Language\LanguageController;
use Illuminate\Support\Facades\Route;

Route::post('languages/set-locale', [LanguageController::class, 'setLocale'])
    ->name('languages.set-locale');
