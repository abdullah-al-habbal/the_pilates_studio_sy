<?php
declare(strict_types=1);
namespace App\Http\Actions\Web\Admin\Operations;
use App\Handlers\Admin\Operations\UpdatePackageHandler;
use App\Http\Requests\Admin\Operations\UpdatePackageRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
final readonly class UpdatePackageAction
{
    use ApiResponseTrait;
    public function __construct(private UpdatePackageHandler $handler) {}
    public function __invoke(UpdatePackageRequest $request, int $packageId): JsonResponse
    {
        try {
            // fix: make a command class instead of passing arg 
            $package = $this->handler->handle(
                $packageId,
                $request->name,
                (int) $request->total_credits,
                $request->has('validity_days') ? (int) $request->validity_days : null,
                (int) $request->currency_id,
                (int) $request->amount,
            );
            return $this->success($package, 'Package updated.');
        } catch (\Throwable $e) {
            Log::error('UpdatePackageAction failed: '.$e->getMessage(), ['exception' => $e]);
            return $this->unprocessable($e->getMessage());
        }
    }
}
