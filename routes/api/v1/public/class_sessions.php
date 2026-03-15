<?php

// filePath: routes/api/v1/public/class_sessions.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\ClassSession\ClassSessionController;
use Illuminate\Support\Facades\Route;

Route::get('class-sessions', [ClassSessionController::class, 'index'])->name('class-sessions.index');
Route::get('class-sessions/{id}', [ClassSessionController::class, 'show'])->name('class-sessions.show');
