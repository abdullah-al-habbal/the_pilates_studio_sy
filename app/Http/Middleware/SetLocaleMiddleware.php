<?php

namespace App\Http\Middleware;

use App\Repositories\Eloquent\Language\LanguageEloquentRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function __construct(
        private readonly LanguageEloquentRepository $languageRepo
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        Log::info('Determining locale for incoming request.');
        $locale = $this->determineLocale($request);
        $this->applyLocale($locale);

        return $next($request);
    }

    private function determineLocale(Request $request): string
    {
        if ($locale = $this->getUserPreferredLocale($request)) {
            return $locale;
        }

        if ($locale = $this->getHeaderPreferredLocale($request)) {
            return $locale;
        }

        if ($locale = $this->getDefaultLocale()) {
            return $locale;
        }

        return $this->getFallbackLocale();
    }

    private function getUserPreferredLocale(Request $request): ?string
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            Log::info('No authenticated user found, skipping user preferred locale.');
            return null;
        }

        $user->loadMissing('settings.preferredLanguage');
        Log::info('Authenticated user found, checking preferred language.', [
            'user_id' => $user->id,
            'preferred_language' => $user->settings?->preferredLanguage?->code,
        ]);
        $userLocale = $user->settings?->preferredLanguage?->code;

        return $this->isActiveLocale($userLocale) ? $userLocale : null;
    }

    private function getHeaderPreferredLocale(Request $request): ?string
    {
        $available = $this->getActiveLocales();
        $headerLocale = $request->getPreferredLanguage($available);

        return $this->isActiveLocale($headerLocale) ? $headerLocale : null;
    }

    private function getDefaultLocale(): ?string
    {
        return $this->languageRepo->getDefault()?->code;
    }

    private function getFallbackLocale(): string
    {
        return config('app.locale', 'en');
    }

    private function getActiveLocales(): array
    {
        return $this->languageRepo->getActiveLocaleCodes();
    }

    private function applyLocale(string $locale): void
    {
        App::setLocale($locale);
    }

    private function isActiveLocale(?string $code): bool
    {
        if (!$code) {
            return false;
        }

        return $this->languageRepo->existsActiveByCode($code);
    }
}
