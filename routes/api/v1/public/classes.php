<?php

// filePath: routes/api/v1/public/classes.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Classes\ClassesController;
use Illuminate\Support\Facades\Route;

Route::get('classes', [ClassesController::class, 'index'])->name('classes.index');
Route::get('classes/{id}', [ClassesController::class, 'show'])->name('classes.show');
