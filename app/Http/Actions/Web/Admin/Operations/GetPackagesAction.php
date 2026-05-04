<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetPackagesHandler;
use App\Http\Resources\Admin\Operations\PackageResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class GetPackagesAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetPackagesHandler $handler
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $packages = $this->handler->handle();

            return $this->success(
                data: PackageResource::collection($packages),
                message: 'Packages retrieved successfully.'
            );
        } catch (\Throwable $e) {
            Log::error('Operations - GetPackages failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->error(message: 'Failed to retrieve packages.');
        }
    }
}
