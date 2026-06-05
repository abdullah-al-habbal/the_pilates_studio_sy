<?php
// routes/web/scheduler/index.php
declare(strict_types=1);

use App\Http\Actions\Web\Admin\Scheduler\{
    IndexAction,
    GetInstructorsAction,
};
use Illuminate\Support\Facades\Route;

Route::prefix('admin/scheduler')
    ->middleware(['web', 'auth', 'freeze.user', 'role.admin'])
    ->name('admin.scheduler.')
    ->group(function (): void {
        Route::get('/', IndexAction::class)->name('index');
        Route::get('/instructors', GetInstructorsAction::class)->name('instructors.index');
        require __DIR__ . '/sessions.php';
        require __DIR__ . '/walkin.php';
        require __DIR__ . '/users.php';
    });
