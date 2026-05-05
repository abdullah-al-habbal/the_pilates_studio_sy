<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\AssignPackageHandler;
use App\Http\Requests\Admin\Operations\AssignPackageRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class AssignPackageAction
{
    use ApiResponseTrait;

    public function __construct(
        private AssignPackageHandler $handler
    ) {}

    public function __invoke(AssignPackageRequest $request, int $packageId): JsonResponse
    {
        try {
            $booking = $this->handler->handle(
                (int) $request->user_id,
                $packageId,
                $request->has('currency_id') ? (int) $request->currency_id : null,
                $request->has('paid_amount') ? (int) $request->paid_amount : null
            );

            return $this->created(
                data: $booking,
                message: 'Package assigned successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - AssignPackage failed: ' . $e->getMessage(), [
                'exception' => $e,
                'package_id' => $packageId,
                'user_id' => $request->user_id,
            ]);
            return $this->unprocessable($e->getMessage());
        }
    }
}
