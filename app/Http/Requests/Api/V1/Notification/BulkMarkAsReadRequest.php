<?php

// filePath: app/Http/Requests/Api/V1/Notification/BulkMarkAsReadRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use App\Http\Requests\Api\BaseApiFormRequest;

class BulkMarkAsReadRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // todo: we must ensure that the notifications belong to the authenticated user, this can be done in the controller, validate ownership of notifications in the controller, or we can create a custom validation rule to check ownership of notifications
        return [
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:app_notifications,id'],
        ];
    }
}
