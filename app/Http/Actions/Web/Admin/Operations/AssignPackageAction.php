<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\AssignPackageHandler;
use App\Http\Requests\Admin\Operations\AssignPackageRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class AssignPackageAction
{
    use ApiResponseTrait;

    public function __construct(
        private AssignPackageHandler $handler,
    ) {}

    public function __invoke(AssignPackageRequest $request, int $packageId): JsonResponse
    {
        $isHistorical = $request->isHistorical();

        try {
            $command = $request->toCommand($packageId);
            $booking = $this->handler->handle($command);

            return $this->created(
                data: $booking,
                message: $isHistorical
                    ? __('dashboard.operations_ui.historical_backfill.success')
                    : __('dashboard.operations_ui.assign_package.success'),
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Operations - AssignPackage failed: ' . $e->getMessage(), [
                'exception' => $e,
                'package_id' => $packageId,
                'user_id' => $request->input('user_id'),
                'historical' => $isHistorical,
            ]);

            return $this->unprocessable($e->getMessage());
        }
    }
}
