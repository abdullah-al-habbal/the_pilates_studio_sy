<?php

// filePath: app/Services/Language/LanguageService.php
declare(strict_types=1);

namespace App\Services\Language;

use App\Models\User;
use App\Repositories\Eloquent\Language\LanguageEloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class LanguageService
{
    public function __construct(
        private readonly LanguageEloquentRepository $languageRepository
    ) {}

    public function getActiveLanguages(): Collection
    {
        return $this->languageRepository->getActiveLanguages();
    }

    public function setUserLocale(User $user, string $code): array
    {
        $language = $this->languageRepository->findActiveByCodeOrFail($code);

        $user->settings()
            ->firstOrCreate(['user_id' => $user->id])
            ->update(['preferred_language_id' => $language->id]);

        return [
            'locale' => $language->code,
            'direction' => $language->direction,
        ];
    }

    public function isValidLocale(string $code): bool
    {
        return $this->languageRepository->existsActiveByCode($code);
    }
}
