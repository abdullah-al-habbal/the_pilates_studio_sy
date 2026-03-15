<?php

// filePath: routes\api\v1\protected\auth.php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
Route::get('me', [AuthController::class, 'me'])->name('me');
