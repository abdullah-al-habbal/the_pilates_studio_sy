<?php
declare(strict_types=1);
namespace App\Http\Actions\Web\Admin\Operations;
use App\Handlers\Admin\Operations\CreatePackageHandler;
use App\Http\Requests\Admin\Operations\CreatePackageRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
final readonly class CreatePackageAction
{
    use ApiResponseTrait;
    public function __construct(private CreatePackageHandler $handler)
    {
    }
    public function __invoke(CreatePackageRequest $request): JsonResponse
    {
        try {
            // fix: make a command class instead of passing args
            $package = $this->handler->handle(
                $request->name,
                (int) $request->total_credits,
                $request->has('validity_days') ? (int) $request->validity_days : null,
                (int) $request->currency_id,
                (int) $request->amount,
            );
            return $this->success($package, 'Package created.');
        } catch (\Throwable $e) {
            Log::error('CreatePackageAction failed: ' . $e->getMessage(), ['exception' => $e]);
            return $this->unprocessable($e->getMessage());
        }
    }
}
