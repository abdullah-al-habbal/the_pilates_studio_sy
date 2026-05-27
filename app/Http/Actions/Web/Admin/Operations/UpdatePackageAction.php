<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\UpdatePackageHandler;
use App\Http\Requests\Admin\Operations\UpdatePackageRequest;
use Illuminate\Http\JsonResponse;

final readonly class UpdatePackageAction
{
    
    public function __construct(
        private UpdatePackageHandler $handler
    ) {}

    public function __invoke(UpdatePackageRequest $request, int $package): JsonResponse
    {
        $package = $this->handler->handle(
            packageId: (int) $package,
            name: $request->input('name'),
            totalCredits: (int) $request->input('total_credits'),
            validityDays: $request->input('validity_days'),
            amount: (int) $request->input('amount'),
        );
        // fix: use the Api Response Trait, not the response()->json()
        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }
}