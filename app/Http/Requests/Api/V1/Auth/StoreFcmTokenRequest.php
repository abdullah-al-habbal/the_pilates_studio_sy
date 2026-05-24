<?php
declare(strict_types=1);
namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;

final class StoreFcmTokenRequest extends BaseApiFormRequest
{
    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:500'],
        ];
    }
}