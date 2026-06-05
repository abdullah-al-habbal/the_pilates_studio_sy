<?php

declare(strict_types=1);

use App\Actions\Debug\SendFcmTestNotificationAction;
use Illuminate\Support\Facades\Route;

Route::get('/debug/fcm', SendFcmTestNotificationAction::class)->name('debug.fcm');