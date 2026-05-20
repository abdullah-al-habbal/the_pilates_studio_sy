<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\DeletePackageHandler;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class DeletePackageAction
{
    use ApiResponseTrait;

    public function __construct(
        private DeletePackageHandler $handler
    ) {
    }

    public function __invoke(int $packageId): JsonResponse
    {
        try {
            $this->handler->handle($packageId);

            return $this->success(null, 'Package deleted.');
        } catch (\Throwable $e) {
            Log::error('DeletePackageAction failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->unprocessable($e->getMessage());
        }
    }
}
