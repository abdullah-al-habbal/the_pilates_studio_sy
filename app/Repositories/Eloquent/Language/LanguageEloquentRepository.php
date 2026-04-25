<?php

// filePath: app/Repositories/Eloquent/Language/LanguageEloquentRepository.php
declare(strict_types=1);

namespace App\Repositories\Eloquent\Language;

use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;

class LanguageEloquentRepository
{
    public function getActiveLanguages(): Collection
    {
        return Language::getActive();
    }

    public function findActiveByCode(string $code): ?Language
    {
        return Language::where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    public function findActiveByCodeOrFail(string $code): Language
    {
        return Language::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function existsActiveByCode(string $code): bool
    {
        return Language::where('code', $code)
            ->where('is_active', true)
            ->exists();
    }

    public function getDefault(): ?Language
    {
        return Language::where('is_default', true)->first()
            ?? Language::where('is_active', true)->first();
    }

    public function getActiveLocaleCodes(): array
    {
        return Language::where('is_active', true)->pluck('code')->toArray();
    }
}
