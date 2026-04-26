<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\Language\LanguageService;
use Illuminate\View\Component;
use Illuminate\View\View;

class LanguageSwitcher extends Component
{
    public function __construct(
        private readonly LanguageService $languageService
    ) {
    }

    public function render(): View
    {
        $languages = $this->languageService->getActiveLanguages();
        $currentLocale = app()->getLocale();

        return view('components.language-switcher', compact('languages', 'currentLocale'));
    }
}
