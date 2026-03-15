<?php

// filePath: routes/api/v1/public/languages.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Language\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('languages', [LanguageController::class, 'index'])
    ->name('languages.index');
