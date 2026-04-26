<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Repositories\Eloquent\Language\LanguageEloquentRepository;
use Closure;
use Illuminate\Http\Request;

class SetLocaleMiddleware
{
    public function __construct(
        private readonly LanguageEloquentRepository $languageRepository
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $locale = null;

        if (session()->has('locale')) {
            $candidate = session('locale');
            if ($this->languageRepository->existsActiveByCode($candidate)) {
                $locale = $candidate;
            }
        }

        if ($request->hasHeader('x-locale')) {
            $headerLocale = $request->header('x-locale');
            if ($this->languageRepository->existsActiveByCode($headerLocale)) {
                $locale = $headerLocale;
            }
        }

        if (!$locale) {
            $defaultLanguage = $this->languageRepository->getDefault();
            $locale = $defaultLanguage?->code ?? config('app.fallback_locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
