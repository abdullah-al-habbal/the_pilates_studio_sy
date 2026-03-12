<?php

// filePath: app/Http/Requests/Api/V1/Setting/UpdateSettingRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Setting;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateSettingRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferred_language_id' => ['sometimes', 'integer', 'exists:languages,id'],
            'allow_notifications'   => ['sometimes', 'boolean'],
            'fcm_token'             => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
