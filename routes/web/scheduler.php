<?php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\Scheduler\IndexAction;
use App\Http\Actions\Web\Admin\Scheduler\GetDailySessionsAction;
use App\Http\Actions\Web\Admin\Scheduler\GetSessionDetailsAction;
use App\Http\Actions\Web\Admin\Scheduler\GetUsersListAction;
use App\Http\Actions\Web\Admin\Scheduler\UpdateAttendanceAction;
use App\Http\Actions\Web\Admin\Scheduler\ProcessExistingWalkInAction;
use App\Http\Actions\Web\Admin\Scheduler\ProcessNewWalkInAction;
use App\Http\Actions\Web\Admin\Scheduler\ValidateWalkInFieldAction;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/scheduler')
    ->middleware(['web', 'auth'])
    ->name('admin.scheduler.')
    ->group(function (): void {
        Route::get('/', IndexAction::class)->name('index');
        Route::get('/sessions', GetDailySessionsAction::class)->name('sessions');
        Route::get('/sessions/{sessionId}', GetSessionDetailsAction::class)->name('session');
        Route::get('/users', GetUsersListAction::class)->name('users');
        Route::get('/walkin/validate', ValidateWalkInFieldAction::class)->name('walkin.validate');
        Route::post('/sessions/{sessionId}/attendance/{bookingSessionId}', UpdateAttendanceAction::class)->name('attendance');
        Route::post('/sessions/{sessionId}/walkin/existing', ProcessExistingWalkInAction::class)->name('walkin.existing');
        Route::post('/sessions/{sessionId}/walkin/new', ProcessNewWalkInAction::class)->name('walkin.new');
    });