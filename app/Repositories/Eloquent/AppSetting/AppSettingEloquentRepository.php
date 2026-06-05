<?php
// filePath: app/Repositories/Eloquent/AppSetting/AppSettingEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\AppSetting;

use App\Models\AppSetting;

class AppSettingEloquentRepository
{
    public function __construct(
        private readonly AppSetting $model
    ) {}

    public function getByKey(string $key): ?AppSetting
    {
        return $this->model->where('key', $key)->first();
    }

    public function index(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->all();
    }
}
