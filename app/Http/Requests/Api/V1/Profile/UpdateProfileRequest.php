<?php

// filePath: app/Http/Requests/Api/V1/Profile/UpdateProfileRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseApiFormRequest
{

    public function rules(): array
    {
        return [
            'fullname'      => ['sometimes', 'string', 'max:255'],
            'phone_number'  => ['sometimes', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'email'         => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }
}
