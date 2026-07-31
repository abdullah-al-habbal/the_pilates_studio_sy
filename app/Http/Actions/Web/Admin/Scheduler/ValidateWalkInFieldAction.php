<?php

// app/Http/Actions/Web/Admin/Scheduler/ValidateWalkInFieldAction.php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Enums\Api\SuccessCodeEnum;
use App\Handlers\Admin\Scheduler\ValidateWalkInFieldHandler;
use App\Http\Requests\Admin\Scheduler\ValidateWalkInFieldRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class ValidateWalkInFieldAction
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ValidateWalkInFieldHandler $handler
    ) {}

    public function __invoke(ValidateWalkInFieldRequest $request): JsonResponse
    {
        $data = $this->handler->handle(
            field: $request->validated('field'),
            value: $request->validated('value'),
        );

        return $this->success(
            data: $data,
            code: SuccessCodeEnum::SUCCESS
        );
    }
}
