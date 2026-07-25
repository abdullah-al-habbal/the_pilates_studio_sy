<?php

declare(strict_types=1);
use App\Http\Actions\Web\Admin\Operations\AssignPackageAction;
use App\Http\Actions\Web\Admin\Operations\CreatePackageAction;
use App\Http\Actions\Web\Admin\Operations\DeletePackageAction;
use App\Http\Actions\Web\Admin\Operations\GetPackagesAction;
use App\Http\Actions\Web\Admin\Operations\UpdatePackageAction;
use Illuminate\Support\Facades\Route;

Route::prefix('packages')->name('packages.')->group(function (): void {
    Route::get('/', GetPackagesAction::class)->name('index');
    Route::post('/', CreatePackageAction::class)->name('store');
    Route::put('/{package}', UpdatePackageAction::class)->name('update');
    Route::delete('/{package}', DeletePackageAction::class)->name('destroy');
    Route::post('/{packageId}/assign', AssignPackageAction::class)->name('assign');
});
