<?php
// filePath: app/Http/Requests/Api/V1/UserSetting/UpdateUserSettingRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\UserSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferred_language_id' => ['nullable', 'exists:languages,id'],
            'allow_notifications' => ['nullable', 'boolean'],
            'fcm_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
