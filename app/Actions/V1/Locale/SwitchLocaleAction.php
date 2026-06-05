<?php

declare(strict_types=1);

namespace App\Actions\V1\Locale;

use App\Services\Language\LanguageService;
use Illuminate\Http\RedirectResponse;

final readonly class SwitchLocaleAction
{
    public function __construct(
        private LanguageService $languageService
    ) {
    }

    public function __invoke(string $code): RedirectResponse
    {
        if ($this->languageService->isValidLocale($code)) {
            session(['locale' => $code]);
            session(['spatie_translatable_active_locale' => $code]);
        }

        return redirect()->back();
    }
}
