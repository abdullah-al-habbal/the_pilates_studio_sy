<?php

// filePath: routes/api/v1/protected/notifications.php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('{id}', [NotificationController::class, 'show'])->name('show');
    Route::patch('bulk/read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-read');
    Route::patch('{id}/read', [NotificationController::class, 'markAsRead'])
        ->whereNumber('id')
        ->name('mark-read');
});
