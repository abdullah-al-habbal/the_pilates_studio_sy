<?php

// filePath: app/Http/Requests/Api/V1/Auth/VerifyOtpRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;

class VerifyOtpRequest extends BaseApiFormRequest
{

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'string', 'size:4'],
        ];
    }
}
