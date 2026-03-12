<?php

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-locale', fn() => 'OK')->middleware(SetLocaleMiddleware::class);
});

test('uses user preferred locale when active', function () {
    Language::factory()->create(['code' => 'fr', 'is_active' => true]);

    $user = User::factory()->create();
    $user->settings()->create([
        'preferred_language_id' => Language::where('code', 'fr')->first()->id,
    ]);

    $this->actingAs($user)
        ->get('/test-locale')
        ->assertOk();

    expect(App::getLocale())->toBe('fr');
});

test('uses header locale when active and user has none', function () {
    Language::factory()->create(['code' => 'de', 'is_active' => true]);

    $this->withHeaders([
        'Accept-Language' => 'de'
    ])->get('/test-locale')->assertOk();

    expect(App::getLocale())->toBe('de');
});

test('uses default locale when no user or header locale matches', function () {
    Language::factory()->create(['code' => 'es', 'is_active' => true, 'is_default' => true]);

    $this->get('/test-locale')->assertOk();

    expect(App::getLocale())->toBe('es');
});

test('falls back to app.locale when nothing else matches', function () {
    config(['app.locale' => 'en']);

    $this->get('/test-locale')->assertOk();

    expect(App::getLocale())->toBe('en');
});
