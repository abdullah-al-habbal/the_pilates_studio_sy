<?php
// filePath: app/Services/AppSetting/AppSettingService.php

declare(strict_types=1);

namespace App\Services\AppSetting;

use App\Models\AppSetting;
use App\Repositories\Eloquent\AppSetting\AppSettingEloquentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppSettingService
{
    public function __construct(
        private readonly AppSettingEloquentRepository $repository
    ) {}

    public function getByKey(string $key): AppSetting
    {
        $setting = $this->repository->getByKey($key);

        if (!$setting) {
            throw new ModelNotFoundException(
                "App setting with key '{$key}' not found."
            );
        }

        return $setting;
    }

    public function getAll()
    {
        return $this->repository->index();
    }
}
