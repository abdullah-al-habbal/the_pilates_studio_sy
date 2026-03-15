<?php

// filePath: app/Http/Requests/Api/V1/Language/SetLocaleRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Language;

use App\Http\Requests\Api\BaseApiFormRequest;

class SetLocaleRequest extends BaseApiFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'exists:languages,code'],
        ];
    }
}
