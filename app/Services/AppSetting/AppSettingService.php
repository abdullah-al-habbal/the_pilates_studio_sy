<?php

declare(strict_types=1);

namespace App\Services\AppSetting;

use App\Repositories\Eloquent\AppSetting\AppSettingEloquentRepository;
use Illuminate\Support\Collection;

class AppSettingService
{
    public function __construct(
        private readonly AppSettingEloquentRepository $repository
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->repository->getByKey($key);
        if (!$setting) {
            return $default;
        }
        $value = $setting->value;
        $type = $setting->type ?? 'string';
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => (int) $value,
            'image' => $value ?: $default,
            'json' => json_decode($value, true),
            'hex_color' => $value ?: $default,
            default => $value,
        };
    }

    public function getTranslated(string $key, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $value = $this->get($key);
        if (is_array($value)) {
            return $value[$locale] ?? $value['en'] ?? null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded[$locale] ?? $decoded['en'] ?? null;
            }
        }
        return is_string($value) ? $value : null;
    }

    public function getByKey(string $key): mixed
    {
        return $this->get($key);
    }

    public function getAll(): Collection
    {
        return $this->repository->index();
    }
}
