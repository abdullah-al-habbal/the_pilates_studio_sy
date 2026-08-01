<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\UserSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:500'],
        ];
    }
}
