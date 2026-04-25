<?php
// app/Http/Actions/Web/Admin/Scheduler/ValidateWalkInFieldAction.php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ValidateWalkInFieldAction
{
    use ApiResponseTrait;

    private const ALLOWED_FIELDS = ['phone_number', 'email'];

    public function __invoke(Request $request): JsonResponse
    {
        $field = $request->query('field');
        $value = $request->query('value');

        if (!in_array($field, self::ALLOWED_FIELDS, true) || empty($value)) {
            return $this->error(
                code: ErrorCodeEnum::VALIDATION_FAILED,
                message: 'Invalid field. Allowed: phone_number, email.',
                status: 422
            );
        }

        $exists = User::whereNull('deleted_at')
            ->where($field, $value)
            ->exists();

        return $this->success(
            data: [
                'field' => $field,
                'value' => $value,
                'available' => !$exists,
            ],
            code: SuccessCodeEnum::SUCCESS
        );
    }
}
