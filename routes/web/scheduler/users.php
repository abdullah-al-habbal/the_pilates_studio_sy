<?php
declare(strict_types=1);
use App\Http\Actions\Web\Admin\Scheduler\GetUsersListAction;
use Illuminate\Support\Facades\Route;
Route::get('/users', GetUsersListAction::class)->name('users');
