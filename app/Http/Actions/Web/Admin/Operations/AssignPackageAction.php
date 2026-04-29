<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\AssignPackageHandler;
use App\Http\Requests\Admin\Operations\AssignPackageRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final readonly class AssignPackageAction
{
    use ApiResponseTrait;

    public function __construct(
        private AssignPackageHandler $handler
    ) {}

    /**
     * Assign a package to a user using validated request.
     */
    public function __invoke(AssignPackageRequest $request, int $packageId): JsonResponse
    {
        try {
            $booking = $this->handler->handle(
                (int) $request->user_id,
                $packageId
            );

            return $this->created(
                data: $booking,
                message: 'Package assigned successfully.'
            );
        } catch (\Exception $e) {
            return $this->unprocessable($e->getMessage());
        }
    }
}
