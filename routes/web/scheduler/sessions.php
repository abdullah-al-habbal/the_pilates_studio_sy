<?php
// routes/web/scheduler/sessions.php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\Scheduler\{
    GetDailySessionsAction,
    GetSessionDetailsAction,
    UpdateAttendanceAction,
};
use Illuminate\Support\Facades\Route;

Route::prefix('sessions')->name('sessions.')->group(function (): void {
    Route::get('/', GetDailySessionsAction::class)->name('index');
    Route::get('/{sessionId}', GetSessionDetailsAction::class)->name('show');
    Route::post('/{sessionId}/attendance/{bookingSessionId}', UpdateAttendanceAction::class)->name('attendance');
});
