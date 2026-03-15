<?php

// filePath: routes/api/v1/protected/packages.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Package\PackageController;
use Illuminate\Support\Facades\Route;

Route::get('packages/{id}', [PackageController::class, 'show'])->name('packages.show');
