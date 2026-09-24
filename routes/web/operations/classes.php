<?php

declare(strict_types=1);

use App\Http\Actions\Web\Admin\Operations\ClassesManagementAction;
use Illuminate\Support\Facades\Route;

Route::prefix('classes')->name('classes.')->controller(ClassesManagementAction::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/options', 'options')->name('options');
    Route::get('/lookup', 'lookup')->name('lookup');
    Route::post('/preview', 'preview')->name('preview');
    Route::post('/', 'store')->name('store');
    Route::get('/{classId}/view', 'detail')->name('detail');
    Route::get('/{classId}/edit', 'editPage')->name('edit-page');
    Route::get('/{classId}', 'show')->name('show');
    Route::put('/{classId}', 'update')->name('update');
    Route::post('/{classId}/status', 'setStatus')->name('status');
    Route::post('/{classId}/restore', 'restore')->name('restore');
    Route::post('/{classId}/images', 'uploadImage')->name('images.store');
    Route::post('/{classId}/images/{imageId}/primary', 'setPrimaryImage')->name('images.primary');
    Route::delete('/{classId}/images/{imageId}', 'deleteImage')->name('images.destroy');
    Route::post('/{classId}/sessions', 'storeSession')->name('sessions.store');
    Route::put('/{classId}/sessions/{sessionId}', 'updateSession')->name('sessions.update');
    Route::delete('/{classId}/sessions/{sessionId}', 'destroySession')->name('sessions.destroy');
    Route::delete('/{classId}/force', 'forceDestroy')->name('force-destroy');
    Route::delete('/{classId}', 'destroy')->name('destroy');
});
