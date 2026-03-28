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
            'X-App-Name' => ['nullable', 'string'],
            'X-App-Platform' => ['required', 'string', new EnumRule(MobilePlatformEnum::class)],
            'X-App-Version' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'X-App-Name' => $this->header('X-App-Name'),
            'X-App-Platform' => $this->header('X-App-Platform'),
            'X-App-Version' => $this->header('X-App-Version'),
        ]);
    }

    public function validatedAppName(): AppNameEnum
    {
        $value = $this->input('X-App-Name', AppNameEnum::CUSTOMER->value);

        try {
            return AppNameEnum::from($value);
        } catch (\ValueError) {
            return AppNameEnum::CUSTOMER;
        }
    }

    public function validatedPlatform(): MobilePlatformEnum
    {
        $value = (string) $this->input('X-App-Platform');

        return MobilePlatformEnum::from(strtolower($value));
    }

    public function validatedVersion(): string
    {
        $version = (string) $this->input('X-App-Version');

        return $version;
    }
}
