<?php

// filePath: routes/api/v1/public/pages.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\StaticPage\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::get('pages/{slug}', [StaticPageController::class, 'showBySlug'])
    ->name('pages.show');
