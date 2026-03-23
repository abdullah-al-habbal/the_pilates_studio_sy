<?php
// filePath: app/Http/Requests/Api/V1/MobileAppVersion/GetCompatibilityRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\MobileAppVersion;

use App\Enums\MobileAppVersion\AppNameEnum;
use App\Enums\MobileAppVersion\MobilePlatformEnum;
use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rules\Enum as EnumRule;

class GetCompatibilityRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['nullable', new EnumRule(AppNameEnum::class)],
            'platform' => ['required', new EnumRule(MobilePlatformEnum::class)],
            'version'  => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
        ];
    }

    public function validatedAppName(): AppNameEnum
    {
        $value = $this->input('app_name', AppNameEnum::CUSTOMER->value);

        try {
            return AppNameEnum::from($value);
        } catch (\ValueError) {
            return AppNameEnum::CUSTOMER;
        }
    }

    public function validatedPlatform(): MobilePlatformEnum
    {
        $value = (string) $this->input('platform');

        return MobilePlatformEnum::from(strtolower($value));
    }

    public function validatedVersion(): string
    {
        $version = (string) $this->input('version');

        return $version;
    }
}
