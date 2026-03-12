<?php

// filePath: app/Http/Requests/Api/V1/Auth/RegisterRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;

class RegisterRequest extends BaseApiFormRequest
{
    public function rules(): array
    {
        return [
            'fullname'      => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone_number'  => ['required', 'string', 'max:20'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ];
    }
}
